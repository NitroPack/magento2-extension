<?php

namespace NitroPack\NitroPack\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Filesystem\DirectoryList;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Helper\ApiHelper;
use NitroPack\NitroPack\Model\FullPageCache\PurgeInterface;
use NitroPack\SDK\NitroPack;
use NitroPack\NitroPack\Logger\Logger;

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
     * @var PurgeInterface
     * */
    protected $purgeInterface;
    /**
     * @var   \Magento\Store\Api\GroupRepositoryInterface
     * */
    protected $groupRepository;

    protected $logger;

    /**
     * @param   \Magento\Framework\Filesystem\Driver\File        $fileDriver
     * @param DirectoryList $directoryList
     * @param ApiHelper                                        $apiHelper
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Api\GroupRepositoryInterface $groupRepository
     * @param PurgeInterface $purgeInterface
     * */
    public function __construct(
        \Magento\Framework\Filesystem\Driver\File        $fileDriver,

        DirectoryList $directoryList,
        ApiHelper                                        $apiHelper,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        ScopeConfigInterface $_scopeConfig,
        \Magento\Store\Api\GroupRepositoryInterface $groupRepository,
        PurgeInterface $purgeInterface,
        Logger $logger
    )
    {
        $this->directoryList = $directoryList;
        $this->serializer = $serializer;
        $this->fileDriver = $fileDriver;
        $this->purgeInterface = $purgeInterface;
        $this->groupRepository = $groupRepository;
        $this->apiHelper = $apiHelper;
        $this->_scopeConfig = $_scopeConfig;
        $this->logger = $logger;
    }

    /**
     * Enable maintenance mode for specific conditions
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {


        $storeGroup = $this->groupRepository->getList();
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
                    $this->settings->enabled = !empty($this->settings->previous_extension_status) && !is_null($this->settings->previous_extension_status) ? $this->settings->previous_extension_status : true ;
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
                            \NitroPack\SDK\PurgeType::LIGHT_PURGE,
                            "Magento cache flush remove all page cache"
                        );
                        $this->purgeInterface->purge();
                } catch (\Exception $e) {
                    $this->logger->error('SDK exception: ' . $e->getMessage());
                    $cachePath = $rootPath . 'nitro_cache' . DIRECTORY_SEPARATOR . $this->settings->siteId;
                    if ($this->fileDriver->isDirectory($cachePath)) {
                        $this->fileDriver->deleteDirectory($cachePath);
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
