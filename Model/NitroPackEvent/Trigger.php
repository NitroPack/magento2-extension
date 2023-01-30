<?php

namespace NitroPack\NitroPack\Model\NitroPackEvent;


use \NitroPack\HttpClient\HttpClient;
use NitroPack\NitroPack\Api\NitroService;
use \NitroPack\SDK\NitroPack;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Area;
use Psr\Log\LoggerInterface;


class Trigger
{
    protected $sdk = null;
    protected $stores = [];
    protected $settings = null;
    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     * */
    protected $productMetaData;
    /**
     * @var \Magento\Framework\App\DeploymentConfig
     */
    private $deploymentConfig;
    /**
     * @var \Magento\Framework\App\State
     */
    private $state;
    /**
     * @var DirectoryList
     * */
    protected $directoryList;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     * */
    protected $fileDriver;
    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     * */
    protected $serializer;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var \NitroPack\NitroPack\Helper\ApiHelper
     * */
    protected $apiHelper;
    /**
     * @var \NitroPack\NitroPack\Helper\RedisHelper
     * */
    protected $redisHelper;
    /**
     * @var \Magento\Store\Model\Store
     * */
    protected $storeGroups;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     * */
     protected $storeManager;

    public function __construct(
        \Magento\Store\Api\GroupRepositoryInterface $groupRepository,
        DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $fileDriver,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Magento\Framework\App\ProductMetadataInterface $productMetaData,
        \NitroPack\NitroPack\Helper\ApiHelper $apiHelper,
        \NitroPack\NitroPack\Helper\RedisHelper $redisHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Store\Model\Store $store,
        LoggerInterface $logger
    ) {
        $this->apiHelper = $apiHelper;
        $this->redisHelper = $redisHelper;
        $this->serializer = $serializer;
        $this->directoryList = $directoryList;
        $this->productMetaData = $productMetaData;
        $this->logger = $logger;
        $this->store = $store;
        $this->storeManager = $storeManager;
        $this->storeGroups = $groupRepository->getList();
    }

    public function hitEvent($event, $additional_meta_data)
    {
        foreach ($this->storeGroups as $storeId => $storeData) {
            $defaultStoreView = $this->storeManager->getStore($storeData->getDefaultStoreId());
            $websiteUrl = $this->store->isUseStoreInUrl() ? str_replace(
                $defaultStoreView->getCode() . '/',
                '',
                $defaultStoreView->getBaseUrl()
            ) : $defaultStoreView->getBaseUrl(); // get store view name

            $query_data = array(
                'event' => $event,
                'platform' => 'Magento',
                'platform_version' => $this->magentoVersion(),
                'nitropack_extension_version' => NitroService::EXTENSION_VERSION,
                'additional_meta_data' => $additional_meta_data ? json_encode($additional_meta_data) : "{}",
                'domain' => $websiteUrl
            );
            $settingsFilename = $this->apiHelper->getSettingsFilename($storeData->getCode());
            $haveData = $this->apiHelper->readFile($settingsFilename);
            if ($haveData) {
                $this->settings = $this->serializer->unserialize($haveData);
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
                if (is_array($this->settings) && isset($this->settings['siteId'])) {
                    $rootPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
                    try {
                        $rootPath = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR;
                    } catch (\Magento\Framework\Exception\FileSystemException $e) {
                        // fallback to using the module directory
                    }

                    $cachePath = $rootPath . 'nitro_cache' . DIRECTORY_SEPARATOR . $this->settings['siteId'];
                    try {
                        $this->sdk = new NitroPack(
                            $this->settings['siteId'], $this->settings['siteSecret'], null, null, $cachePath
                        );

                        $client = new HttpClient(
                            $this->sdk->integrationUrl('extensionEvent') . '&' . http_build_query($query_data)
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
        }
    }


    public function magentoVersion()
    {
        return $this->productMetaData->getVersion();
    }
}
