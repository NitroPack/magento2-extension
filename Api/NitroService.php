<?php

namespace NitroPack\NitroPack\Api;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\ObjectManagerInterface;
use NitroPack\NitroPack\Helper\RedisHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session;
use Psr\Log\LoggerInterface;
use \NitroPack\SDK\Api\Varnish as NitroPackVarnish;
use \NitroPack\SDK\NitroPack;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;


// Loaded through SDK autoloader


class NitroService implements NitroServiceInterface
{

    const EXTENSION_VERSION = '2.3.3';

    const FULL_PAGE_CACHE_NITROPACK = 'system/full_page_cache/caching_application';
    const FULL_PAGE_CACHE_NITROPACK_VALUE = 3;
    public const XML_VARNISH_PAGECACHE_ACCESS_LIST = 'system/full_page_cache/varnish_nitro/access_list';
    public const XML_VARNISH_PAGECACHE_VARNISH_PORT = 'system/full_page_cache/varnish_nitro/varnish_port';
    public const XML_VARNISH_PAGECACHE_BACKEND_HOST = 'system/full_page_cache/varnish_nitro/backend_host';

    public const XML_VARNISH_PAGECACHE_NITRO_ENABLED = 'system/full_page_cache/varnish_enable';
    protected static $pageRoutes = array(
        'cms_index_index' => 'home',
        'catalog_product_view' => 'product',
        'catalog_category_view' => 'category',
        'cms_page_view' => 'info',
        'contact_index_index' => 'contact'
    );
    /**
     * @var LoggerInterface
     * */
    protected $logger;

    protected $connected = false;
    protected $settings = null;
    protected $sdk = null;
    protected $loadedStoreCode = null;
    protected $varnish = null;
    /**
     * @var StoreManagerInterface
     * */
    protected $storeManager;

    protected $currentStoreId;
    /**
     * @var State
     * */
    protected $appState;

    /**
     * @var ObjectManagerInterface
     * */
    protected $objectManager;
    /**
     * @var DirectoryList
     * */
    protected $directoryList;
    // ! Do not rely on dependency injection for the session and cart, as they may trigger our frontend observers, and we do not want to do that from the constructor, since the observers inject this service, which then would lead to a dependency loop
    protected $session;
    /**
     * @var \Magento\Framework\Filesystem\DriverInterface
     * */
    protected $fileDriver;
    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     * */
    protected $serializer;
    /**
     * @var ScopeConfigInterface
     * */
    private $_scopeConfig;
    /**
     * @var RequestInterface
     * */
    protected $request;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;
    /**
     * @var RedisHelper
     * */
    protected $redisHelper;
    /**
     * @var  \Magento\Store\Model\Store
     * */
    protected $store;
    /**
     * @var  \Magento\Framework\Encryption\EncryptorInterface
     * */
    protected $encryptor;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param State $appState
     * @param DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $fileDriver
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param LoggerInterface $logger
     * @param RedisHelper $redisHelper
     * @param ScopeConfigInterface $_scopeConfig
     * @param UrlInterface $urlBuilder
     * @param \Magento\Store\Model\Store $store
     * @param RequestInterface $request
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * */
    public function __construct(
        ObjectManagerInterface                           $objectManager,
        State                                            $appState,
        DirectoryList                                    $directoryList,
        \Magento\Framework\Filesystem\Driver\File        $fileDriver,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        LoggerInterface                                  $logger,
        RedisHelper                                      $redisHelper,
        ScopeConfigInterface                             $_scopeConfig,
        UrlInterface                                     $urlBuilder,
        \Magento\Store\Model\Store                       $store,
        RequestInterface                                 $request,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor

    )
    {
        $this->_scopeConfig = $_scopeConfig;
        $this->logger = $logger;
        $this->objectManager = $objectManager;
        $this->appState = $appState;
        $this->directoryList = $directoryList;
        $this->fileDriver = $fileDriver;
        $this->serializer = $serializer;
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
        $this->redisHelper = $redisHelper;
        $this->store = $store;
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->loadedStoreCode = null;
        $this->encryptor = $encryptor;

        try {
            if (!$this->readSettings($this->loadedStoreCode)) {
                $this->settings = static::defaultSettings();
            } else {
                $this->sdk = $this->initializeSdk();
                if (!is_null($this->sdk)) {

                    if (
                        !is_null($this->_scopeConfig->getValue(self::FULL_PAGE_CACHE_NITROPACK))
                        && $this->_scopeConfig->getValue(
                            self::FULL_PAGE_CACHE_NITROPACK
                        ) == self::FULL_PAGE_CACHE_NITROPACK_VALUE
                        && !is_null($this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_BACKEND_HOST))
                        && !is_null($this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_NITRO_ENABLED))
                        && $this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_NITRO_ENABLED)
                    ) {
                        // Config url check because the value is reset via configuration
                        if ($this->urlBuilder->getCurrentUrl() != $this->urlBuilder->getUrl('NitroPack/Webhook/Config/') . '?token' . $this->nitroGenerateWebhookToken($this->settings->siteId)) {

                            $backendServer = explode(
                                ',',
                                $this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_BACKEND_HOST)
                            );
                            $varnishPortConfig = $this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_VARNISH_PORT);
                            $backendServer = array_map(function ($backendValue) use ($varnishPortConfig) {
                                if ($backendValue == "localhost") {
                                    if ($varnishPortConfig != 80) {
                                        return "127.0.0.1:" . $varnishPortConfig;
                                    }
                                    return "127.0.0.1";

                                }
                                if ($varnishPortConfig != 80) {
                                    return $backendValue . ':' . $varnishPortConfig;
                                }
                                return $backendValue;

                            }, $backendServer);

                            $this->varnish = $this->initializeVarnish();
                            $url = $this->request->isSecure() ? 'https://' . $this->request->getHttpHost() : 'http://' . $this->request->getHttpHost();
                            try {
                                $this->varnish->configure([
                                    'Servers' => $backendServer,
                                    'PurgeAllUrl' => $url,
                                    'PurgeAllMethod' => 'PURGE',
                                    'PurgeSingleMethod' => 'PURGE',
                                ]);
                                $this->varnish->enable();
                                $this->sdk->setVarnishProxyCacheHeaders([
                                    'X-Magento-Tags-Pattern' => ' .*'
                                ]);
                            } catch (\Exception $e) {
                                $this->logger->debug('Varnish exception:' . $e->getMessage());
                            }
                        }
                    }
                }
            }
            //  $this->sdk->purgeLocalCache(true);
            //exit;
        } catch (\Exception $e) {
            $this->logger->debug('SDK exception:' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * \NitroPack\NitroPack\Api\NitroServiceInterface::extensionVersion
     */
    public function extensionVersion()
    {
        return NitroService::EXTENSION_VERSION;
    }

    /**
     * \NitroPack\NitroPack\Api\NitroServiceInterface::sdkVersion
     */
    public function sdkVersion()
    {
        return NitroPack::VERSION;
    }

    public function magentoVersion()
    {
        $mData = $this->objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        return $mData->getVersion();
    }

    /**
     * \NitroPack\NitroPack\Api\NitroServiceInterface::reload
     */
    public function reload($storeGroup = null, $url = null)
    {
        try {
            if (!$this->readSettings($storeGroup)) {
                $this->settings = static::defaultSettings();
            } else {
                $this->loadedStoreCode = $storeGroup ? $storeGroup : $this->loadedStoreCode;
                $this->sdk = $this->initializeSdk($url);
                if (is_null($this->sdk)) {
                    if (!$this->isConnected()) {
                        throw new \Exception('SDK exception: re-verification and throw error');
                    } else {
                        throw new \Exception('SDK exception: Disconnected and throw error');
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->debug('SDK exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * \NitroPack\NitroPack\Api\NitroServiceInterface::disconnect
     */
    public function disconnect($storeGroupCode = null)
    {
        $settingsFilename = $this->getSettingsFilename($storeGroupCode);
        if ($this->fileDriver->isExists($settingsFilename) && $this->fileDriver->isWritable($settingsFilename)) {
            unlink($settingsFilename);
        }
    }

    /**
     * \NitroPack\NitroPack\Api\NitroServiceInterface::isConnected
     */
    public function isConnected()
    {
        $connectConditions = (
            $this->fileDriver->isExists($this->getSettingsFilename($this->loadedStoreCode)) &&
            isset($this->settings->siteId) && !empty($this->settings->siteId) &&
            isset($this->settings->siteSecret) && !empty($this->settings->siteSecret)
        );
        $this->logger->debug('Is connected: ' . ($connectConditions ? 'YES' : 'NO'));

        return $connectConditions;
    }

    /**
     * \NitroPack\NitroPack\Api\NitroServiceInterface::isEnabled
     */
    public function isEnabled()
    {
        return $this->settings->enabled;
    }

    /**
     * \NitroPack\NitroPack\Api\NitroServiceInterface::isSafeModeEnabled
     */
    public function isSafeModeEnabled()
    {
         return isset($this->settings->safeMode) ?  $this->settings->safeMode : false;

    }

    /**
     * \NitroPack\NitroPack\Api\NitroServiceInterface::getSettings
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * \NitroPack\NitroPack\Api\NitroServiceInterface::setWarmupSettings
     */
    public function setWarmupSettings($enabled = null, $pageTypes = null, $currencies = null)
    {
        $this->settings->cacheWarmup = ($enabled !== null) ? $enabled : $this->settings->cacheWarmup;
        $this->settings->warmupTypes = ($pageTypes !== null) ? $pageTypes : $this->settings->warmupTypes;
        $this->settings->warmupCurrencyVariations = ($currencies !== null) ? $currencies : $this->settings->warmupCurrencyVariations;
    }

    /**
     * \NitroPack\NitroPack\Api\NitroServiceInterface::getSettings
     */
    public function getBuiltInPageTypeRoutes()
    {
        $inverted = array();
        foreach (static::$pageRoutes as $route => $nitroName) {
            $inverted[$nitroName] = $route;
        }
        return $inverted;
    }

    /**
     * \NitroPack\NitroPack\Api\NitroServiceInterface::getSiteId
     */
    public function getSiteId()
    {
        return $this->settings->siteId;
    }

    /**
     * \NitroPack\NitroPack\Api\NitroServiceInterface::getSiteSecret
     */
    public function getSiteSecret()
    {
        return $this->settings->siteSecret;
    }

    /**
     * \NitroPack\NitroPack\Api\NitroServiceInterface::getSdk
     */
    public function getSdk()
    {
        return $this->sdk;
    }

    /**
     * \NitroPack\NitroPack\Api\NitroServiceInterface::setSiteId
     */
    public function setSiteId($newSiteId)
    {
        $this->settings->siteId = $newSiteId;
    }

    /**
     * \NitroPack\NitroPack\Api\NitroServiceInterface::setSiteSecret
     */
    public function setSiteSecret($newSiteSecret)
    {
        $this->settings->siteSecret = $newSiteSecret;
    }

    /**
     * \NitroPack\NitroPack\Api\NitroServiceInterface::persistSettings
     */
    public function persistSettings($storeName = null)
    {
        if ($storeName == null) {
            $storeName = $this->loadedStoreCode;
        }
        $settingsFilename = $this->getSettingsFilename($storeName);
        if ($this->fileDriver->isExists($settingsFilename)) {
            if (!$this->fileDriver->isWritable($settingsFilename)) {
                // settings file exists but we cannot write to it
                return false;
            }
        } elseif (!$this->fileDriver->isWritable(dirname($settingsFilename))) {
            // settings file does not exist and we cannot write to its directory
            return false;
        }

        $this->fileDriver->filePutContents($settingsFilename, $this->serializer->serialize($this->settings));
        return true;
    }

    /**
     * \NitroPack\NitroPack\Api\NitroServiceInterface::isCacheable
     */
    public function isCacheable()
    {
        if (!$this->session) {
            $this->session = $this->objectManager->get(Session::class);
        }
        if ($this->session->isLoggedIn()) {
            return false;
        }

        return true;
    }

    /**
     * \NitroPack\NitroPack\Api\NitroServiceInterface::isCachableRoute
     */
    public function isCachableRoute($route)
    {

        $result = false;
        if (isset(static::$pageRoutes[$route]) && $this->settings->pageTypes->{static::$pageRoutes[$route]}) {

            return true;
        }
        if (!(array)$this->settings->pageTypes->custom) {
            $this->settings->pageTypes->custom = new \stdClass();
        }
        try {
            if (!is_null($this->settings->pageTypes->custom->{$route}) && $this->settings->pageTypes->custom->{$route} == 0) {
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }
        if (!isset($this->settings->pageTypes->custom->{$route})) {
            $this->settings->pageTypes->custom->{$route} = "0";
            $this->persistSettings();
            return true;
        }
        return $result;
    }

    public static function isANitroRequest()
    {
        return isset($_SERVER['HTTP_X_NITROPACK_REQUEST']);
    }

    // Forward to the SDK object
    public function __call($method, $args)
    {
        if (!$this->sdk || !method_exists($this->sdk, $method)) {
            throw new \BadMethodCallException(
                'Trying to call nonexistant method ' . $method . ' on an object of type ' . get_called_class()
            );
        }
        return call_user_func_array(array($this->sdk, $method), $args);
    }

    // Forward to the SDK object
    public function __get($key)
    {
        if (!$this->sdk || !isset($this->sdk->{$key})) {
            trigger_error(sprintf('Undefined member variable %s', $key), E_USER_NOTICE);
            return null;
        }
        return $this->sdk->{$key};
    }

    // Forward to the SDK object
    public function __isset($key)
    {
        return ($this->sdk && isset($this->sdk->{$key}));
    }

    protected static function defaultSettings(&$target = null, $skipCredentials = false)
    {
        if ($target == null) {
            $settings = new \stdClass();
        } else {
            $settings = $target;
        }

        $settings->enabled = true;
        if (!$skipCredentials) {
            $settings->siteId = null;
            $settings->siteSecret = null;
        }

        if (!isset($settings->autoClear)) {
            $settings->autoClear = new \stdClass();
        }

        $settings->autoClear->products = true;
        $settings->autoClear->attributes = true;
        $settings->autoClear->attributeSets = true;
        $settings->autoClear->reviews = true;
        $settings->autoClear->categories = true;
        $settings->autoClear->pages = true;
        $settings->autoClear->blocks = true;
        $settings->autoClear->widgets = true;
        $settings->autoClear->orders = true;

        if (!isset($settings->pageTypes)) {
            $settings->pageTypes = new \stdClass();
        }

        $settings->pageTypes->home = true;
        $settings->pageTypes->product = true;
        $settings->pageTypes->category = true;
        $settings->pageTypes->info = true;
        $settings->pageTypes->contact = true;

        $settings->pageTypes->custom = array();

        $settings->cacheWarmup = false;

        if (!isset($settings->warmupTypes)) {
            $settings->warmupTypes = new \stdClass;
        }

        $settings->warmupTypes->home = true;
        $settings->warmupTypes->product = true;
        $settings->warmupTypes->category = true;
        $settings->warmupTypes->info = true;
        $settings->warmupTypes->contact = true;

        if (!isset($settings->warmupPriority)) {
            $settings->warmupPriority = new \stdClass;
        }

        $settings->warmupPriority->home = 1.0;
        $settings->warmupPriority->info = 0.9;
        $settings->warmupPriority->contact = 0.8;
        $settings->warmupPriority->category = 0.7;
        $settings->warmupPriority->product = 0.6;

        if (!isset($settings->warmupCurrencyVariations) || !is_array($settings->warmupCurrencyVariations)) {
            $settings->warmupCurrencyVariations = array();
        }

        return $settings;
    }

    protected function readSettings($storeName = null)
    {
        $settingsFilename = $this->getSettingsFilename($storeName);
        if ($this->fileDriver->isExists($settingsFilename) && $this->fileDriver->isReadable($settingsFilename)) {
            $contents = $this->fileDriver->fileGetContents($settingsFilename);

            if (!$contents) {
                return false;
            }
            try {
                $this->settings = json_decode($contents);
            } catch (\Exception $e) {
                $this->logger->debug("Read file error:" . $e->getMessage());
            }
            if ($this->settings) {
                return true;
            }
        }
        return false;
    }

    public function getSettingsFilename($storeName = null)
    {
        $rootPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;

        try {
            $rootPath = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR;
        } catch (\Magento\Framework\Exception\FileSystemException $e) {
            // fallback to using the module directory
        }

        if ($storeName === null) {
            // check if in admin or frontend
            $area = $this->appState->getAreaCode();

            if ($area == Area::AREA_FRONTEND) {
                if (!$this->storeManager) {
                    $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
                }
                $storeName = $this->storeManager->getGroup()->getCode();
            } elseif ($area == Area::AREA_ADMINHTML) {
                return $rootPath . 'nitro_settings_NO_STORE.json';
            }
        }
        return $rootPath . 'nitro_settings_' . $storeName . '.json';
    }

    protected function initializeSdk($url = null)
    {
        if (!$this->settings->siteId || !$this->settings->siteSecret) {
            return null;
        }

        $rootPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;

        try {
            $rootPath = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR;
        } catch (\Magento\Framework\Exception\FileSystemException $e) {
            // fallback to using the module directory
        }

        $cachePath = $rootPath . 'nitro_cache' . DIRECTORY_SEPARATOR . $this->settings->siteId;

        if (!$this->fileDriver->isExists($cachePath)) {
            mkdir($cachePath, 0777, true);
        }
        $checkRedisConfigure = $this->redisHelper->validatedRedisConnection();
        if ($checkRedisConfigure) {
            \NitroPack\SDK\Filesystem::setStorageDriver(
                new \NitroPack\SDK\StorageDriver\Redis(
                    $checkRedisConfigure['host'],
                    $checkRedisConfigure['port'],
                    $checkRedisConfigure['pass'],
                    $checkRedisConfigure['db']
                )
            );
        }

        try {
            return new NitroPack($this->settings->siteId, $this->settings->siteSecret, null, $url, $cachePath);
        }catch (\Exception $e){

            return null;
        }
    }

    public function nitroEvent($event, $integrationUrl, $storeGroup = null, $additional_meta_data = null)
    {
        try {
            if ($storeGroup) {
                $defaultStoreView = $this->storeManager->getStore($storeGroup->getDefaultStoreId());

                $websiteUrl = $this->store->isUseStoreInUrl() ? str_replace(
                    $defaultStoreView->getCode() . '/',
                    '',
                    $defaultStoreView->getBaseUrl()
                ) : $defaultStoreView->getBaseUrl(); // get store view name
            } else {
                $websiteUrl = $this->storeManager->getStore()->getBaseUrl();
            }

            $query_data = array(
                'event' => $event,
                'platform' => 'Magento',
                'platform_version' => $this->magentoVersion(),
                'nitropack_extension_version' => NitroService::EXTENSION_VERSION,
                'additional_meta_data' => $additional_meta_data ? json_encode($additional_meta_data) : "{}",
                'domain' => $websiteUrl
            );

            $httpClientData = $integrationUrl . '&' . http_build_query($query_data);
            $client = new \NitroPack\HttpClient\HttpClient($httpClientData);
            $client->doNotDownload = true;
            $client->fetch();
            if ($client->getStatusCode() === 200) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function checkHealthStatus()
    {
        return $this->sdk->checkHealthStatus();
    }

    public function initializeVarnish()
    {
        return new NitroPackVarnish($this->settings->siteId, $this->settings->siteSecret);
    }

    public function isCheckCartOrCustomerRoute($route)
    {
        if (strpos(strtolower($route), '/checkout') !== false || strpos(strtolower($route), '/customer') !== false) {
            return true;
        }
        return false;
    }


    public function nitroGenerateWebhookToken($siteId)
    {

        return $this->encryptor->hash($this->directoryList->getPath('var') . ":" . $siteId);
    }


}
