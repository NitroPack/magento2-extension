<?php

namespace NitroPack\NitroPack\Observer;


use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Filesystem\DirectoryList;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Helper\ApiHelper;
use NitroPack\NitroPack\Helper\VarnishHelper;
use NitroPack\SDK\NitroPack;

class MaintenanceModeObserver implements ObserverInterface
{
    protected $settings = null;
    /**
     * @var ApiHelper
     * */
    protected $apiHelper;
    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     * */
    protected $serializer;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     * */
    protected $fileDriver;
    /**
     * @var DirectoryList
     * */
    protected $directoryList;

    protected $sdk;

    /**
     * @var ScopeConfigInterface
     * */
    protected $_scopeConfig;

    /**
     * @var VarnishHelper
     * */
    protected $varnishHelper;
    /**
     * @param   \Magento\Framework\Filesystem\Driver\File        $fileDriver
     * @param DirectoryList $directoryList
     * @param ApiHelper                                        $apiHelper
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param ScopeConfigInterface $scopeConfig
     * @param VarnishHelper $varnishHelper
     * */
    public function __construct(
        \Magento\Framework\Filesystem\Driver\File        $fileDriver,
        DirectoryList $directoryList,
        ApiHelper                                        $apiHelper,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        ScopeConfigInterface $_scopeConfig,
        VarnishHelper $varnishHelper


    )
    {
        $this->directoryList = $directoryList;
        $this->serializer = $serializer;
        $this->fileDriver = $fileDriver;
        $this->varnishHelper = $varnishHelper;
        $this->apiHelper = $apiHelper;
        $this->_scopeConfig = $_scopeConfig;
    }

    /**
     * Enable maintenance mode for specific conditions
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeRepo = $objectManager->create(\Magento\Store\Api\GroupRepositoryInterface::class);
        $storeGroup = $storeRepo->getList();
        foreach ($storeGroup as $storesData) {
            $settingsFilename = $this->apiHelper->getSettingsFilename($storesData->getCode());
            $haveData = $this->apiHelper->readFile($settingsFilename);
            if ($haveData) {
                $this->settings = json_decode($haveData);
                if ($observer['isOn']) {
                    $this->settings->previous_extension_status = $this->settings->enabled;
                    if ($this->settings->enabled) {
                        $this->settings->enabled = false;
                    }
                } else {
                    $this->settings->enabled = !is_null($this->settings->previous_extension_status) ? $this->settings->previous_extension_status : true ;
                }

                try {
                    $rootPath = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR;
                } catch (\Magento\Framework\Exception\FileSystemException $e) {
                    // fallback to using the module directory
                }

                $cachePath = $rootPath . 'nitro_cache' . DIRECTORY_SEPARATOR . $this->settings->siteId;
                try {
                    $this->sdk = new NitroPack(
                        $this->settings->siteId, $this->settings->siteSecret, null, null, $cachePath
                    );

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

                } catch (\Exception $e) {
                    $file = $objectManager->create('\Magento\Framework\Filesystem\Driver\File');
                    $cachePath = $rootPath . 'nitro_cache' . DIRECTORY_SEPARATOR . $this->settings->siteId;
                    if ($file->isDirectory($cachePath)) {
                        $file->deleteDirectory($cachePath);
                    }
                }
                $this->fileDriver->filePutContents(
                    $settingsFilename,
                    $this->serializer->serialize($this->settings)
                );
            }
        }


    }
}
