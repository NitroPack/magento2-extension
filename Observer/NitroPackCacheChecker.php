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
namespace NitroPack\NitroPack\Observer;

use Magento\Framework\App\Area;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Filesystem\DirectoryList;
use NitroPack\HttpClient\HttpClient;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Helper\ApiHelper;
use NitroPack\SDK\NitroPack;
use NitroPack\NitroPack\Logger\Logger;

/**
 * Class NitroPackCacheChecker - NitroPack Cache Checker Full Page Cache Enabled Observer
 * @implements ObserverInterface
 * @package NitroPack\NitroPack\Observer
 * @since 2.0.0
 * */
class NitroPackCacheChecker implements ObserverInterface
{

    /**
     * @var \Magento\Framework\App\State
     */
    private $state;
    /**
     * @var DirectoryList
     * */
    protected $directoryList;
    protected $settings;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     * */
    protected $fileDriver;
    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     * */
    protected $serializer;

    /**
     * @var ApiHelper
     * */
    protected $apiHelper;

    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     * */
    protected $productMetaData;

    public function __construct(
        \Magento\Framework\App\State $state,
        DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $fileDriver,
        ApiHelper $apiHelper,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        Logger $logger
    ) {
        $this->state = $state;
        $this->serializer = $serializer;
        $this->fileDriver = $fileDriver;
        $this->apiHelper = $apiHelper;
        $this->directoryList = $directoryList;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeRepo = $objectManager->create(\Magento\Store\Api\GroupRepositoryInterface::class);
        $stores = $storeRepo->getList();
        foreach ($stores as $storesData) {

            $settingsFilename = $this->apiHelper->getSettingsFilename($storesData->getCode());
            $haveData = $this->apiHelper->readFile($settingsFilename);
            if ($haveData) {
                $this->settings = json_decode($haveData);
                // Checking previous status
                if (isset($this->settings->previous_extension_status) && $this->settings->previous_extension_status === false) {
                    if ($observer->getData('cache_enabled')) {
                        $this->settings->enabled = false;
                        $this->fileDriver->filePutContents(
                            $settingsFilename,
                            $this->serializer->serialize($this->settings)
                        );

                        $this->apiHelper->triggerEventMultipleStore(
                            'disable_extension',
                            false,
                            $storesData,
                            $this->settings
                        );
                    }
                } else {
                    $this->settings->enabled = $observer->getData('extension');
                    $this->fileDriver->filePutContents(
                        $settingsFilename,
                        $this->serializer->serialize($this->settings)
                    );
                    $this->apiHelper->triggerEventMultipleStore(
                        $observer->getData('extension') ? 'enable_extension' : 'disable_extension',
                        false,
                        $storesData,
                        $this->settings
                    );
                }
            }
        }
    }


}
