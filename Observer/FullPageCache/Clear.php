<?php

namespace NitroPack\NitroPack\Observer\FullPageCache;

use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Filesystem\DirectoryList;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Helper\ApiHelper;
use NitroPack\NitroPack\Helper\RedisHelper;
use NitroPack\NitroPack\Helper\VarnishHelper;
use NitroPack\SDK\NitroPack;

class Clear implements ObserverInterface
{
    /**
     * @var DirectoryList
     * */
    protected $directoryList;

    protected $settings = null;
    protected $sdk = null;
    /**
     * @var ApiHelper
     * */
    protected $apiHelper;
    /**
     * @var RedisHelper
     * */
    protected $redisHelper;
    /**
     * @var ScopeConfigInterface
     * */
    protected $_scopeConfig;
    /**
     * @var VarnishHelper
     * */
    protected $varnishHelper;
    /**
     * @var StateInterface;
     * */
    private   $_cacheState;
    /**
     * @param DirectoryList $directoryList
     * @param ApiHelper $apiHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param VarnishHelper $varnishHelper
     * @param  StateInterface $_cacheState
     * */
    public function __construct(
        DirectoryList $directoryList,
        ApiHelper $apiHelper,
        VarnishHelper $varnishHelper,
        RedisHelper $redisHelper,
        ScopeConfigInterface $scopeConfig,
        StateInterface $_cacheState

    ) {
        $this->redisHelper = $redisHelper;
        $this->apiHelper = $apiHelper;
        $this->varnishHelper = $varnishHelper;
        $this->_scopeConfig = $scopeConfig;
        $this->directoryList = $directoryList;
        $this->_cacheState = $_cacheState;
    }

    public function execute(Observer $observer)
    {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeRepo = $objectManager->create(\Magento\Store\Api\GroupRepositoryInterface::class);
        $storeGroup = $storeRepo->getList();
        if (!is_null(
                $this->_scopeConfig->getValue('system/full_page_cache/caching_application')
            ) && $this->_scopeConfig->getValue(
                'system/full_page_cache/caching_application'
            ) == NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE && $this->_cacheState->isEnabled('full_page')) {

            foreach ($storeGroup as $storesData) {
                $settingsFilename = $this->apiHelper->getSettingsFilename($storesData->getCode());
                $haveData = $this->apiHelper->readFile($settingsFilename);
                if ($haveData) {
                    $this->settings = json_decode($haveData);

                    $rootPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
                    try {
                        $rootPath = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR;
                    } catch (\Magento\Framework\Exception\FileSystemException $e) {
                        // fallback to using the module directory
                    }

                    $cachePath = $rootPath . 'nitro_cache' . DIRECTORY_SEPARATOR . $this->settings->siteId;
                    try {
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
                        $this->sdk = new NitroPack(
                           $this->settings->siteId, $this->settings->siteSecret, null, null, $cachePath
                        );

                        if ($this->settings->enabled) {
                            $this->sdk->purgeCache(
                                null,
                                null,
                                \NitroPack\SDK\PurgeType::COMPLETE,
                                "Magento cache flush remove all page cache"
                            );
                            if (
                                !is_null($this->_scopeConfig->getValue(NitroService::FULL_PAGE_CACHE_NITROPACK))
                                && $this->_scopeConfig->getValue(
                                    NitroService::FULL_PAGE_CACHE_NITROPACK
                                ) == NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE

                            ) {
                                $this->varnishHelper->purgeVarnish();
                            }
                        }
                    } catch (\Exception $e) {
                        $file = $objectManager->create('\Magento\Framework\Filesystem\Driver\File');
                        $cachePath = $rootPath . 'nitro_cache' . DIRECTORY_SEPARATOR . $this->settings->siteId;
                        if ($file->isDirectory($cachePath)) {
                            $file->deleteDirectory($cachePath);
                        }
                    }
                }
            }
        }
    }
}
