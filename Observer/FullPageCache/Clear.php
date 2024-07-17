<?php
/**
 * NitroPack
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the nitropack.io license that is
 * available through the world-wide-web at this URL:
 * https://github.com/NitroPack/magento2-extension/blob/716247d40d2de7b84f222c6a93761d87b6fe5b7b/LICENSE
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Site Optimization
 * @subcategory Performance
 * @package     NitroPack_NitroPack
 * @author      NitroPack Inc.
 * @copyright   Copyright (c) NitroPack (https://www.nitropack.io/)
 * @license     https://github.com/NitroPack/magento2-extension/blob/716247d40d2de7b84f222c6a93761d87b6fe5b7b/LICENSE
 */
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
use NitroPack\SDK\PurgeType;

/**
 * Class Clear - Cache Flush Observer
 * @implements ObserverInterface
 * @package NitroPack\NitroPack\Observer\FullPageCache
 * @since 2.8.0
 * */
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

        $eventName = $observer->getEvent()->getName();
        $eventReason = $this->getReasonForPurge($eventName);
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

                            $purgeType = ($eventName == 'clean_catalog_images_cache_after') ? PurgeType::COMPLETE : PurgeType::LIGHT_PURGE;

                            $this->sdk->purgeCache(
                                null,
                                null,
                                $purgeType,
                                $eventReason
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

    public function getReasonForPurge($eventName)
    {
        if($eventName== 'assign_theme_to_stores_after'){
         return "Magento theme assigned to stores,so cache flush remove all page cache";
        }
        if($eventName== 'clean_media_cache_after'){
            return "Magento images clean,so cache flush remove all page cache";
        }
        if($eventName== 'clean_catalog_images_cache_after'){
            return "Magento catalog images clean,so cache flush remove all page cache";
        }
        return "Magento cache flush remove all page cache";
    }
}
