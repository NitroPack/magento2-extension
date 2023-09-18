<?php

namespace NitroPack\NitroPack\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\PageCache\Model\Config;
use Magento\Framework\HTTP\Client\Curl;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\SDK\Api\ResponseStatus;
use Magento\Framework\Module\Dir;
use Composer\Composer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\HTTP\Header;
use Magento\Framework\HTTP\ZendClientFactory;

class DiagnosticsReport extends AbstractHelper
{
    /**
     * @var ProductMetadataInterface
     * */
    private $productMetadata;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     * */
    private $storeManager;
    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     * */
    protected $dir;
    /**
     * @var \Magento\Framework\Module\Dir
     * */
    protected $moduleDir;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     * */
    protected $fileDriver;
    /**
     * @var \Magento\Framework\Module\FullModuleList
     * */
    protected $fullModuleList;
    /**
     * @var \Magento\Framework\Module\Manager
     * */
    protected $moduleManager;

    /**
     * @var ObjectManagerInterface
     * */
    protected $objectManager;
    /**
     * @var RedisHelper
     * */
    protected $redisHelper;
    /**
     * @var ScopeConfigInterface
     **/
    protected $scopeConfig;
    /**
     * @var ZendClientFactory
     */
    private $httpClientFactory;

    /**
     * @var Header
     */
    private $httpHeader;

    /**
     * @var \Magento\PageCache\Model\Config
     * */
    protected   $config;
    /**
     * @var \Magento\Framework\App\DeploymentConfig
     * */
    protected $deploymentConfig;
    /**
     * @var DirectoryList
     * */
    protected $directoryList;
    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     * */
    protected $encryptor;
    /**
     * @var Context $context
     * @var ProductMetadataInterface $productMetadata
     * @var \Magento\Store\Model\StoreManagerInterface $storeManager
     * @var \Magento\Framework\Filesystem\DirectoryList $dir
     * @var \Magento\Framework\Filesystem\Driver\File $fileDriver
     * @var Dir $moduleDir
     * @var \Magento\Framework\Module\FullModuleList $fullModuleList
     * @var \Magento\Framework\Module\Manager $moduleManager
     * @var ScopeConfigInterface $scopeConfig
     * @var RedisHelper $redisHelper
     * @var ObjectManagerInterface $objectManager
     * @param ZendClientFactory $httpClientFactory
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig = null
     * @param Header $httpHeader
     * @param \Magento\PageCache\Model\Config  $config
     * @param DirectoryList $directoryList
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     *
     * */
    public function __construct(
        Context                                     $context,
        ProductMetadataInterface                    $productMetadata,
        \Magento\Store\Model\StoreManagerInterface  $storeManager,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Framework\Filesystem\Driver\File   $fileDriver,
        Dir                                         $moduleDir,
        \Magento\Framework\Module\FullModuleList    $fullModuleList,
        \Magento\Framework\Module\Manager           $moduleManager,
        ScopeConfigInterface                        $scopeConfig,
        RedisHelper                                 $redisHelper,
        ObjectManagerInterface                      $objectManager,
        ZendClientFactory $httpClientFactory,
        Header $httpHeader,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig = null,
        \Magento\PageCache\Model\Config  $config,
        DirectoryList $directoryList,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor

    )
    {
        $this->config = $config;
        $this->httpClientFactory = $httpClientFactory;
        $this->httpHeader = $httpHeader;
        $this->redisHelper = $redisHelper;
        $this->objectManager = $objectManager;
        $this->moduleDir = $moduleDir;
        $this->scopeConfig = $scopeConfig;
        $this->fileDriver = $fileDriver;
        $this->productMetadata = $productMetadata;
        $this->dir = $dir;
        $this->moduleManager = $moduleManager;
        $this->fullModuleList = $fullModuleList;
        $this->storeManager = $storeManager;
        $this->deploymentConfig  = $deploymentConfig;
        $this->directoryList = $directoryList;
        $this->encryptor = $encryptor;
        parent::__construct($context);
    }
    function getDirInfo($nitro,$nitroConfig) {

        // DoI = Directories of Interest
        $nitroDataDir = str_replace('/pagecache', '', $nitro->getCacheDir());
        $nitroSiteIDDir = str_replace('/data/pagecache', '', $nitro->getCacheDir());

        $DoI = array(
            'NitroPack_Cache_Dir_Writable' =>  $nitro->getCacheDir() ,
            'Nitro_Data_Dir_Writable' => $nitroDataDir,
            'Nitro_siteID_Dir_Writable' => $nitroSiteIDDir,
        );

        $info = array();
        foreach ($DoI as $doi_dir => $dpath) {
            if (is_dir($dpath)) {
                $info[$doi_dir] = is_writeable($dpath) ? true : false;
            } else if (is_file($dpath)) {
                $info[$doi_dir] = $dpath. __( ' is a file not a directory');
            } else {
                $info[$doi_dir] =  __( 'Directory not found');
            }
        }

        return $info;
    }
    function getGeneralInfo($nitro, $nitroConfig)
    {
        if (!is_null($nitro)) {
            $probe_result = "OK";
            try {
                $nitro->fetchConfig();
            } catch (\Exception $e) {
                $probe_result = __('Error: ', 'nitropack') . $e->getMessage();
            }
        } else {
            $probe_result = __('Error: Cannot get an SDK instance', 'nitropack');
        }
        $third_party_residual_cache = $this->detectThirdPartyCache();

        $nitroDataDir = str_replace('/pagecache', '', $nitro->getCacheDir());
        $configFileName = $nitroDataDir . '/' . $nitroConfig['siteId'] . '-config.json';
        $info = array(
            'Nitro_MAG_version' => $this->productMetadata->getVersion(),
            'Nitro_Version' => NitroService::EXTENSION_VERSION,
            'Nitro_SDK_Connection' => $probe_result,
            'Nitro_API_Polling' => $nitro ? $this->pollApi($nitro) : __('Error: Cannot get an SDK instance', 'nitropack'),
            'Nitro_SDK_Version' => \NitroPack\SDK\Nitropack::VERSION,
            'Nitro_Cache_MAG' => $this->checkMagentoCache(),
            'Nitro_Plugin_Directory' => $this->moduleDir->getDir('NitroPack_NitroPack'),
            'Nitro_Data_Directory' => $nitro ? $nitroDataDir : __('Undefined', 'nitropack'),
            'Nitro_Config_File' => ($this->fileDriver->isExists($configFileName)) ? $configFileName : __('Undefined', 'nitropack'),
            'Nitro_Backlog_File_Status' => $nitro ? $this->backlogStatus($nitro) : __('Error: Cannot get an SDK instance', 'nitropack'),
            'Nitro_Webhooks' => $nitro ? $this->compareWebhooks($nitro, $nitroConfig) : __('Error: Cannot get an SDK instance', 'nitropack'),
            'Nitro_Connectivity_Requirements' => $this->nitropackCheckFuncAvailability('stream_socket_client') ? __('OK', 'nitropack') : __('Warning: "stream_socket_client" function is disabled.', 'nitropack'),
            'Residual_Cache_Found_For' => $third_party_residual_cache,
        );
        $info['Nitro_Cache_Method'] = 'plugin';
        return $info;
    }

    function getUserConfig($nitro, $nitroConfig)
    {

        $nitroDataDir = str_replace('/pagecache', '', $nitro->getCacheDir());
        $configFileName = $nitroDataDir . '/' . $nitroConfig['siteId'] . '-config.json';

        if ($this->fileDriver->isExists($configFileName)) {
            $info = json_decode(file_get_contents($configFileName), true);
            $unsetNitroUserConfig = ["RevisionHash", "Telemetry", "SDKVersion", "LastFetch", "IgnoredParams", "URLPathVersion", "ExcludedCookies", "Version", "DisabledURLs", "EnabledURLs", "LoopbackRequests", "AjaxURLs"];
            foreach ($unsetNitroUserConfig as $unsetNitroUserConfigValue)
                unset($info[$unsetNitroUserConfigValue]);
            $composer = new Composer();
            $version = $composer::VERSION;
            $info['PHP_VERSION'] = PHP_VERSION;
            $info['COMPOSER_VERSION'] = $version;
            $info['isConnectedWithElasticSearch'] = $this->isConnectedToElasticsearch();
            $info['isConnectedWithOpenSearch'] = $this->isConnectedToOpenSearch();
            $info['isConnectedWithRedis'] = $this->redisHelper->validatedRedisConnection();
            $info['isConnectedWithVarnishSelfHosted'] = $this->isConnectedToVarnish();
            $info['isConnectedWithRabbitMq'] = $this->isRabbitMQConnected();
            if (!$info) {
                $info = __('Config found, but unable to get contents.', 'nitropack');
            }
        } else {
            $info = __('Config file not found.', 'nitropack');
        }
        return $info;
    }


    public function pollApi($nitro_sdk)
    {
        $pollResult = array(
            ResponseStatus::OK => __('OK', 'nitropack'),
            ResponseStatus::ACCEPTED => __('OK', 'nitropack'),
            ResponseStatus::BAD_REQUEST => __('Bad request.', 'nitropack'),
            ResponseStatus::PAYMENT_REQUIRED => __('Payment required. Please, contact NP support for details.', 'nitropack'),
            ResponseStatus::FORBIDDEN => __('Site disabled. Please, contact NP support for details.', 'nitropack'),
            ResponseStatus::NOT_FOUND => __('URL used for the API poll request returned 404. Please ignore this.', 'nitropack'),
            ResponseStatus::CONFLICT => __('Conflict. There is another operation, which prevents accepting optimization requests at the moment. Please, contact NP support for details.', 'nitropack'),
            ResponseStatus::RUNTIME_ERROR => __('Runtime error.', 'nitropack'),
            ResponseStatus::SERVICE_UNAVAILABLE => __('Service unavailable.', 'nitropack'),
            ResponseStatus::UNKNOWN => __('Unknown.', 'nitropack')
        );

        try {
            $referer = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : '';
            $apiResponseCode = $nitro_sdk->getApi()->getCache($this->storeManager->getStore()->getBaseUrl(), __('NitroPack Diagnostic Agent', 'nitropack'), array(), false, 'default', $referer)->getStatus();
            return $pollResult[$apiResponseCode];
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    function backlogStatus($nitro)
    {
        return $nitro->backlog->exists() ? 'Warning' : 'OK';
    }

    function compareWebhooks($nitro, $siteConfig)
    {
        try {
            if (!empty($siteConfig['siteId'])) {
                 $constructedWH = $this->storeManager->getStore()->getBaseUrl() . 'NitroPack/Webhook/Config/?token='.$this->nitroGenerateWebhookToken($siteConfig['siteId']);

                 $storedWH = $nitro->getApi()->getWebhook("config");

                $matchResult = ($constructedWH == $storedWH) ? __('OK', 'nitropack') : __('Warning: Webhooks do not match this site', 'nitropack');
            } else {
                $debugMsg = empty($_SERVER["HTTP_HOST"]) ? "HTTP_HOST is not defined. " : "";
                $debugMsg .= empty($_SERVER["REQUEST_URI"]) ? "REQUEST_URI is not defined. " : "";
                $debugMsg .= empty($debugMsg) ? 'URL used to match config was: ' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] : "";
                $matchResult = __('Site config cannot be found, because ', 'nitropack') . $debugMsg;
            }
            return $matchResult;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }


    function nitropackCheckFuncAvailability($func_name)
    {
        if (function_exists('ini_get')) {
            $existsResult = stripos(ini_get('disable_functions'), $func_name) === false;
        } else {
            $existsResult = function_exists($func_name);
        }
        return $existsResult;
    }

    function checkMagentoCache()
    {

        return $this->config->getType() === NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE  && $this->config->isEnabled();
    }


    function detectThirdPartyCache()
    {
        $info = "";
        if (!empty($info)) {
            return $info;
        } else {
            return $info = __('Not found', 'nitropack');
        }
    }

    public function getActivePluginsStatus()
    {

        $activeModuleFilter = array_filter($this->fullModuleList->getAll(), function ($value) {
            return $this->moduleManager->isOutputEnabled($value);
        }, ARRAY_FILTER_USE_KEY);
        return array_keys($activeModuleFilter);
    }


    /**
     * Check if Magento is connected to Elasticsearch
     *
     * @return bool
     */
    public function isConnectedToElasticsearch(): bool
    {
        $engine = $this->scopeConfig->getValue('catalog/search/engine', 'default');
        if ($engine === 'elasticsearch6' || $engine === 'elasticsearch7' || $engine === 'elasticsearch') {
            // Elasticsearch is configured as the search engine
            return  true;
        }
        return false;
    }

    /**
     * Check if Magento is connected to OpenSearch
     * * @return bool
     */
    public function isConnectedToOpenSearch(): bool
    {
        // Assuming you have installed and configured the OpenSearch extension properly
        $engine = $this->scopeConfig->getValue('catalog/search/engine', 'default');
        if ($engine === 'opensearch') {
          return true;

        }
        return false;
    }

    public function isConnectedToVarnish(): bool
    {
        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'http://localhost');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PURGE');
            $headers = array();
            $headers[] = 'X-Magento-Tags-Pattern: .*';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch,CURLOPT_TIMEOUT,10);
            curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,10);
            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                return false;
            }
            curl_close($ch);
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * Check if RabbitMQ is connected or not
     *
     * @return bool
     */
    public function isRabbitMQConnected(): bool
    {
        try {
            $curl = new Curl();
            $curl->setCredentials(
                $this->deploymentConfig->get('queue/amqp/user'),
                $this->deploymentConfig->get('queue/amqp/password')
            );
            $curl->addHeader('content-type', 'application/json');
            $curl->get(  $this->deploymentConfig->get('queue/amqp/host') . 'overview');
            $data = $curl->getBody();
            $data = json_decode($data, true);
            return isset($data['management_version']);

        } catch (\Exception $e) {
            return false;
        }
    }


    public function nitroGenerateWebhookToken($siteId)
    {
        return $this->encryptor->hash($this->directoryList->getPath('var'). ":" . $siteId);
    }
}
