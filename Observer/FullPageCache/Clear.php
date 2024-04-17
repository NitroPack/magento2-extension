<?php

namespace NitroPack\NitroPack\Observer\FullPageCache;

use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Filesystem\DirectoryList;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Helper\ApiHelper;
use NitroPack\NitroPack\Helper\FastlyHelper;
use NitroPack\NitroPack\Helper\RedisHelper;
use NitroPack\NitroPack\Model\FullPageCache\PurgeInterface;
use NitroPack\SDK\NitroPack;
use NitroPack\NitroPack\Logger\Logger;

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
     * @var PurgeInterface
     * */
    protected $purgeInterface;
    /**
     * @var StateInterface;
     * */
    private $_cacheState;
    /**
     * @var FastlyHelper
     * */
    protected $fastlyHelper;

    protected $logger;

    /**
     * @param DirectoryList $directoryList
     * @param ApiHelper $apiHelper
     * @param FastlyHelper $fastlyHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param PurgeInterface $purgeInterface
     * @param RedisHelper $redisHelper
     * @param StateInterface $_cacheState
     * */
    public function __construct(
        DirectoryList        $directoryList,
        ApiHelper            $apiHelper,
        FastlyHelper         $fastlyHelper,
        PurgeInterface       $purgeInterface,
        RedisHelper          $redisHelper,
        ScopeConfigInterface $scopeConfig,
        StateInterface       $_cacheState,
        Logger $logger
    )
    {
        $this->fastlyHelper = $fastlyHelper;
        $this->apiHelper = $apiHelper;
        $this->purgeInterface = $purgeInterface;
        $this->_scopeConfig = $scopeConfig;
        $this->redisHelper = $redisHelper;
        $this->directoryList = $directoryList;
        $this->_cacheState = $_cacheState;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeRepo = $objectManager->create(\Magento\Store\Api\GroupRepositoryInterface::class);
        $storeGroup = $storeRepo->getList();
        if ($this->_scopeConfig->getValue(NitroService::FULL_PAGE_CACHE_NITROPACK)
            && in_array($this->_scopeConfig->getValue(
                NitroService::FULL_PAGE_CACHE_NITROPACK
            ), [NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE, NitroService::FASTLY_CACHING_APPLICATION_VALUE]) && $this->_cacheState->isEnabled('full_page')) {

            if($this->fastlyHelper->isFastlyAndNitroDisable()){

                return false;
            }

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
                        if (!is_null($checkRedisConfigure) && $checkRedisConfigure) {
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
                            //Check NitroPack With Fastly Disable
                            if ($this->fastlyHelper->isFastlyAndNitroDisable()) {
                                return;
                            }
                            $this->sdk->purgeCache(
                                null,
                                null,
                                \NitroPack\SDK\PurgeType::COMPLETE,
                                "Magento cache flush remove all page cache"
                            );

                            $this->purgeInterface->purge();

                        }
                    } catch (\Exception $e) {
                        $this->logger->error($e->getMessage());
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
