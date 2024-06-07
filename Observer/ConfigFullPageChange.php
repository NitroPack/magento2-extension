<?php

namespace NitroPack\NitroPack\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer as EventObserver;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Helper\InvalidationHelper;
use Magento\Framework\App\Config\Storage\WriterInterface;
use NitroPack\NitroPack\Helper\ApiHelper;
use NitroPack\NitroPack\Model\FullPageCache\PurgeInterface;
use NitroPack\SDK\HealthStatus;

class ConfigFullPageChange implements \Magento\Framework\Event\ObserverInterface
{
    public const XML_VARNISH_PAGECACHE_NITRO_ENABLED = 'system/full_page_cache/varnish_enable';
    public const FULL_PAGE_CACHE_NITROPACK = 'system/full_page_cache/caching_application';
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var ScopeConfigInterface
     * */
    private $_scopeConfig;
    /**
     * @var PurgeInterface
     * */
    private $purgeInterface;

    protected $settings;
    protected $sdk = null;
    /**
     * @var ApiHelper
     * */
    protected $apiHelper;
    /**
     * @var  \Magento\Framework\App\Cache\TypeListInterface
     * */
    protected $_cacheTypeList;
    /**
     * @var  \Magento\Framework\App\Cache\Frontend\Pool
     * */
    protected $_cacheFrontendPool;
    /**
     * @var  \Magento\Framework\Filesystem\Driver\File
     * */
    protected $fileDriver;
    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     * */
    protected $serializer;
    /**
     * @var InvalidationHelper
     * */
    protected $invalidationHelper;

    /**
     * @var WriterInterface
     * */
    protected $configWriter;
    /**
     * @var NitroService
     * */
    protected $nitro;
    /**
     * @var \Magento\Store\Api\GroupRepositoryInterface
     * */
    protected $storeRepository;
    /**
     * ConfigChange constructor.
     * @param RequestInterface $request
     * @param ScopeConfigInterface $scopeConfig
     * @param PurgeInterface $purgeInterface
     * @param InvalidationHelper $invalidationHelper
     * @param \Magento\Framework\Filesystem\Driver\File $fileDriver
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param WriterInterface $configWriter
     * @param NitroServiceInterface $nitro
     * @param \Magento\Store\Api\GroupRepositoryInterface $StoreRepository
     * @param \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
     * */
    public function __construct(
        RequestInterface $request,
        ScopeConfigInterface $scopeConfig,
        PurgeInterface $purgeInterface,
        InvalidationHelper $invalidationHelper,
        \Magento\Framework\Filesystem\Driver\File $fileDriver,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        WriterInterface $configWriter,
        NitroServiceInterface $nitro,
        \Magento\Store\Api\GroupRepositoryInterface $storeRepository,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
    ) {
        $this->request = $request;
        $this->nitro = $nitro;
        $this->_scopeConfig = $scopeConfig;
        $this->purgeInterface = $purgeInterface;
        $this->invalidationHelper = $invalidationHelper;
        $this->serializer = $serializer;
        $this->fileDriver = $fileDriver;
        $this->_cacheTypeList = $cacheTypeList;
        $this->configWriter = $configWriter;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        $this->storeRepository = $storeRepository;
    }

    public function execute(EventObserver $observer)
    {
        $groupParams = $this->request->getParam('groups');
        if(!is_null($groupParams)){
            if(isset($groupParams['full_page_cache']) && isset($groupParams['full_page_cache']['fields']) && isset($groupParams['full_page_cache']['fields']['varnish_enable'])) {
                $varnishEnableKey = $groupParams['full_page_cache']['fields']['varnish_enable']['value'];
                //Check Properly Configure ENABLED && DISABLED
                if (!is_null(
                        $this->_scopeConfig->getValue(self::XML_VARNISH_PAGECACHE_NITRO_ENABLED)
                    ) && $this->_scopeConfig->getValue(self::XML_VARNISH_PAGECACHE_NITRO_ENABLED) == $varnishEnableKey) {
                    $storeGroup = $this->storeRepository->getList();
                    foreach ($storeGroup as $storesData) {
                        try {
                            $this->nitro->reload($storesData->getCode());
                            $this->varnishConfiguredSetup();
                        }catch (\Exception $e) {
                        }
                    }
                    $this->purgeInterface->purge();
                }
         }
        $nitroCacheKey = $groupParams['full_page_cache']['fields']['caching_application']['value'];
        $serviceEnable = true;
        if (!is_null($this->_scopeConfig->getValue(self::FULL_PAGE_CACHE_NITROPACK)) && $this->_scopeConfig->getValue(
                self::FULL_PAGE_CACHE_NITROPACK
            ) == $nitroCacheKey && $nitroCacheKey != NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE) {
            if (!is_null(
                    $this->_scopeConfig->getValue(self::FULL_PAGE_CACHE_NITROPACK)
                ) && $this->_scopeConfig->getValue(
                    self::FULL_PAGE_CACHE_NITROPACK
                ) == \Magento\PageCache\Model\Config::BUILT_IN ) {
                $this->purgeInterface->purge();
            }

            $storeGroup = $this->storeRepository->getList();

            foreach ($storeGroup as $storesData) {
                try {
                    $this->nitro->reload($storesData->getCode());
                    if (!is_null($this->nitro->getSdk()) && $this->nitro->getSdk()->getHealthStatus() == HealthStatus::HEALTHY) {
                        $varnish = $this->nitro->initializeVarnish();
                        $varnish->disable();
                    }
                }catch (\Exception $e) {
                }
            }
            $serviceEnable = false;
        }

        //
        $this->invalidationHelper->setEnableAndDisable($serviceEnable);
        $this->configWriter->save('full_page_cache/fields/caching_application/value', $nitroCacheKey);
        $types = array(
            'config',
            'layout',
            'block_html',
            'collections',
            'reflection',
            'db_ddl',
            'eav',
            'config_integration',
            'config_integration_api',
            'full_page',
            'translate',
            'config_webservice'
        );
        foreach ($types as $type) {
            $this->_cacheTypeList->cleanType($type);
        }
        foreach ($this->_cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
        }
        return $this;

    }

    public function varnishConfiguredSetup()
    {
        try {

            if (
                !is_null($this->_scopeConfig->getValue(NitroService::FULL_PAGE_CACHE_NITROPACK))
                && $this->_scopeConfig->getValue(
                    NitroService::FULL_PAGE_CACHE_NITROPACK
                ) == NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE
                && !is_null($this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_BACKEND_HOST))
                && !is_null($this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_NITRO_ENABLED))
                && $this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_NITRO_ENABLED)
            ) {

                if (!is_null($this->nitro->getSdk()) && $this->nitro->getSdk()->getHealthStatus() == HealthStatus::HEALTHY) {
                    $provideUrl  = $this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_BACKEND_HOST);
                    $url =  'http://'.$provideUrl;
                    // Config url check because the value is reset via configuration
                    $backendServer = explode(
                        ',',
                        $this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_BACKEND_HOST)
                    );

                    $backendServer = array_map(function ($backendValue) {
                        $backendHostAndPort = explode(":", $backendValue);
                        if ($backendHostAndPort[0] == "localhost" || $backendHostAndPort[0] == '127.0.0.1') {
                            if (isset($backendHostAndPort[1]) && $backendHostAndPort[1] == 80) {
                                return "127.0.0.1";
                            }
                            if (isset($backendHostAndPort[1])) {
                                return "127.0.0.1:" . $backendHostAndPort[1];
                            }
                        }
                        return $backendValue;
                    }, $backendServer);

                    $varnish = $this->nitro->initializeVarnish();

                    try {
                        $varnish->configure([
                            'Servers' => $backendServer,
                            'PurgeAllUrl' => $url,
                            'PurgeAllMethod' => 'PURGE',
                            'PurgeSingleMethod' => 'PURGE',
                        ]);
                        $varnishData = [[
                                'PurgeAllMethod' => 'PURGE',
                                'PurgeAllUrl' => $url,
                                'PurgeSingleHeadersTemplates' => [['HeaderName' => 'X-Magento-Tags-Pattern', 'HeaderTemplate' => '.*']],
                                'PurgeSingleTemplate' => $url,
                                'PurgeSingleMethod' => 'PURGE',
                                ]];
                        $this->nitro->getSdk()->getApi()->setVarnishIntegrationConfig($varnishData);
                        $varnish->enable();
                        $this->nitro->getSdk()->setVarnishProxyCacheHeaders([
                            'X-Magento-Tags-Pattern' => ' .*'
                        ]);
                    } catch (\Exception $e) {
                        return false;
                    }

                }

            }
        } catch (\Exception $exception) {
            return false;
        }

    }

}
