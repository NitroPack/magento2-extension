<?php

namespace NitroPack\NitroPack\Helper;

use Magento\Customer\Api\GroupExcludedWebsiteRepositoryInterface;
use Magento\Customer\Model\Context as CustomerContextConstants;
use Magento\Framework\App\Area;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\Store;
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
     * @var \Magento\Customer\Model\GroupFactory
     * */
    protected $groupFactory;
    /**
     * @var ObjectManagerInterface
     * */
    protected $objectManager;
    /**
     * @var GroupExcludedWebsiteRepositoryInterface
     * */
    protected $customerGroupExcludedWebsiteRepository;
    /**
     * @var StoreManagerInterface
     * */
    protected $storeManager;
    /**
     * @param Context $context
     * @param \Magento\Framework\App\State $state
     * @param DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $fileDriver
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param LoggerInterface $logger
     * @param \Magento\Customer\Model\GroupFactory $groupFactory
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface $storeManager
     * @param GroupExcludedWebsiteRepositoryInterface $customerGroupExcludedWebsiteRepository
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetaData
     * */
    public function __construct(
        Context                                          $context,
        \Magento\Framework\App\State                     $state,
        DirectoryList                                    $directoryList,
        \Magento\Framework\Filesystem\Driver\File        $fileDriver,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        LoggerInterface                                  $logger,
        \Magento\Customer\Model\GroupFactory             $groupFactory,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        GroupExcludedWebsiteRepositoryInterface $customerGroupExcludedWebsiteRepository,
        \Magento\Framework\App\ProductMetadataInterface  $productMetaData
    )
    {
        parent::__construct($context);
        $this->state = $state;
        $this->serializer = $serializer;
        $this->groupFactory = $groupFactory;
        $this->fileDriver = $fileDriver;
        $this->productMetaData = $productMetaData;
        $this->logger = $logger;
        $this->objectManager = $objectManager;
        $this->directoryList = $directoryList;
        $this->storeManager = $storeManager;
        $this->customerGroupExcludedWebsiteRepository = $customerGroupExcludedWebsiteRepository;
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

    public function writeFile($settingsFilename, $data)
    {
        $this->fileDriver->filePutContents($settingsFilename, $this->serializer->serialize($data));
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


    public function getAllCustomerGroupDataForPossibleXMagentoVary($storeGroup)
    {
        $customerGroupCollection = $this->groupFactory->create()->getCollection();
        $customerGroupCollection->addFieldToFilter('customer_group_id', ['gt' => 0]);
        $allCustomerXMagentoVary = [];
        $allXMagentoVary = [];
        foreach ($customerGroupCollection->getData() as $customerGroupCollectionValue) {
            $customerGroupWeb = $this->customerGroupExcludedWebsiteRepository->getCustomerGroupExcludedWebsites((int)$customerGroupCollectionValue['customer_group_id']);
            // Get the list of websites associated with the customer group
            if (is_null($this->getStoreViews($storeGroup))) {
                $data = [CustomerContextConstants::CONTEXT_GROUP => (string)$customerGroupCollectionValue['customer_group_id'], CustomerContextConstants::CONTEXT_AUTH => true];
                if (!empty($data)) {
                    ksort($data);
                    $xMagentoVaryValue = sha1($this->serializer->serialize($data));
                    $allXMagentoVary[] = $xMagentoVaryValue;
                    $allCustomerXMagentoVary[$xMagentoVaryValue] = $data;
                }
            } else {
                foreach ($this->getStoreViews($storeGroup) as $storeView) {
                    //Exculde the customer group from the website
                    if (!in_array($storeGroup->getWebsiteId(), $customerGroupWeb)) {
                        if ($storeView != $this->storeManager->getDefaultStoreView()->getCode())
                            $data = ["store" => $storeView, CustomerContextConstants::CONTEXT_GROUP => (string)$customerGroupCollectionValue['customer_group_id'], CustomerContextConstants::CONTEXT_AUTH => true];
                        else
                            $data = [CustomerContextConstants::CONTEXT_GROUP => (string)$customerGroupCollectionValue['customer_group_id'], CustomerContextConstants::CONTEXT_AUTH => true];
                        if (!empty($data)) {
                            ksort($data);
                            $xMagentoVaryValue = sha1($this->serializer->serialize($data));
                            $allXMagentoVary[] = $xMagentoVaryValue;
                            $allCustomerXMagentoVary[$xMagentoVaryValue] = $data;
                        }
                    }
                }
            }

        }
        if (!is_null($this->getStoreViews($storeGroup))) {
            foreach ($this->getStoreViews($storeGroup) as $storeView) {
                if ($storeView != $this->storeManager->getDefaultStoreView()->getCode()){
                    $data = ["store" =>$storeView];
                    $xMagentoVaryValue = sha1($this->serializer->serialize($data));
                    $allXMagentoVary[] = $xMagentoVaryValue;
                    $allCustomerXMagentoVary[$xMagentoVaryValue] = $data;
                }
            }
        }
        return [$allCustomerXMagentoVary, $allXMagentoVary];
    }

    public function getStoreViews($storeGroup)
    {

        $store = $this->objectManager->get(Store::class);
        //Check Add Variation Cookie for Store
        if (!$store->isUseStoreInUrl()) {
            $storeViewCode = [];
            $stores = $storeGroup->getStores();
            foreach ($stores as $storeValue) {
                $storeViewData = $this->storeManager->getStore($storeValue->getCode());
                if($storeViewData->isActive())
                    $storeViewCode[] = $storeValue->getCode(); // get store view name
            }
        }
        if (count($storeViewCode) > 0) {
            return $storeViewCode;
        }
        return null;
    }
}
