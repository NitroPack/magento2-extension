<?php

namespace NitroPack\NitroPack\Helper;

use Magento\Framework\App\Area;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\HttpClient\HttpClient;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\SDK\NitroPack;
use Psr\Log\LoggerInterface;

class ApiHelper extends AbstractHelper

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
     * @var \Magento\Framework\App\ProductMetadataInterface
     * */
    private $productMetaData;
    /**
     * @var LoggerInterface
     * */
    private $logger;

    /**
     * @param Context $context
     * @param \Magento\Framework\App\State $state
     * @param DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $fileDriver
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param LoggerInterface $logger
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetaData
     * */
    public function __construct(
        Context $context,
        \Magento\Framework\App\State $state,
        DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $fileDriver,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        LoggerInterface $logger,
        \Magento\Framework\App\ProductMetadataInterface $productMetaData
    ) {
        $this->state = $state;
        $this->serializer = $serializer;
        $this->fileDriver = $fileDriver;
        $this->productMetaData = $productMetaData;
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        parent::__construct($context);
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
            $area = $this->state->getAreaCode();

            if ($area == Area::AREA_FRONTEND) {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $storeManager = $objectManager->get(StoreManagerInterface::class);
                $storeName = $storeManager->getStore()->getCode();
            } elseif ($area == Area::AREA_ADMINHTML) {
                return $rootPath . 'nitro_settings_NO_STORE.json';
            }
        }
        return $rootPath . 'nitro_settings_' . $storeName . '.json';
    }


    public function readFile($settingsFilename)
    {
        if ($this->fileDriver->isExists($settingsFilename) && $this->fileDriver->isReadable($settingsFilename)) {
            return $this->fileDriver->fileGetContents($settingsFilename);
        }
        return false;
    }

    public function triggerEventMultipleStore($event, $additional_meta_data, $storeGroup, $settings)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->create(\Magento\Store\Model\StoreManagerInterface::class);
        $store = $objectManager->create(\Magento\Store\Model\Store::class);
        $defaultStoreView = $storeManager->getStore($storeGroup->getDefaultStoreId());
        $websiteUrl = $store->isUseStoreInUrl() ? str_replace(
            $defaultStoreView->getCode() . '/',
            '',
            $defaultStoreView->getBaseUrl()
        ) : $defaultStoreView->getBaseUrl(); // get store view name
        $query_data = array(
            'event' => $event,
            'platform' => 'Magento',
            'platform_version' => $this->productMetaData->getVersion(),
            'nitropack_extension_version' => NitroService::EXTENSION_VERSION,
            'additional_meta_data' => $additional_meta_data ? json_encode($additional_meta_data) : "{}",
            'domain' => $websiteUrl
        );
        $rootPath = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR;
        $cachePath = $rootPath . 'nitro_cache' . DIRECTORY_SEPARATOR . $settings->siteId;
        try {
            $sdk = new NitroPack(
                $settings->siteId, $settings->siteSecret, null, null, $cachePath
            );
            $client = new HttpClient(
                $sdk->integrationUrl('extensionEvent') . '&' . http_build_query($query_data)
            );
            $client->timeout = 10;
            $client->fetch();
            if ($client->getStatusCode() != 200) {
                $this->logger->critical($client->getStatusCode() . " NitroPack event service is down");
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
