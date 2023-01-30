<?php

namespace NitroPack\NitroPack\Setup\Patch\Data;


use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Filesystem\DirectoryList as DirectoryListApp;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Module\ResourceInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Store\Model\Store;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Helper\RedisHelper;
use NitroPack\NitroPack\Helper\VarnishHelper;
use Psr\Log\LoggerInterface;


class DataPatch implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var \NitroPack\NitroPack\Model\NitroPackEvent\Trigger
     * */
    protected $trigger;
    /**
     * @var Store
     * */
    protected $store;
    /**
     * @var \Magento\Store\Api\GroupRepositoryInterface
     * */
    protected $storeGroupRepo;
    /**
     * @var \NitroPack\NitroPack\Helper\ApiHelper
     * */
    protected $apiHelper;
    /**
     * @var \Magento\Framework\Filesystem
     * */
    protected $fileSystem;
    /**
     * @var NitroServiceInterface
     * */
    protected $nitro;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     * */
    protected $serializer;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     * */
    protected $fileDrive;
    /**
     * @var DirectoryList
     * */
    protected $directoryList;
    /**
     * @var  \Magento\Store\Model\StoreManagerInterface
     * */
    protected $storeManager;
    /**
     * @var \Magento\Store\Api\StoreRepositoryInterface
     * */
    protected $storeRepository;
    /**
     * @var RedisHelper
     * */
    protected $redisHelper;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     * */
    protected $scopeConfig;
    /**
     * @var VarnishHelper
     * */
    protected $varnishHelper;
    /**
     * @var WriterInterface
     * */
    protected $configWriter;
    /**
     * @var ModuleListInterface
     * */
    protected $moduleList;
    /**
     * @var ResourceInterface
     * */
    protected $moduleResource;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param \NitroPack\NitroPack\Model\NitroPackEvent\Trigger $trigger
     * @param \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepo
     * @param \NitroPack\NitroPack\Helper\ApiHelper $apiHelper
     * @param \Magento\Framework\Filesystem $filesystem
     * @param DirectoryList $directoryList
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Filesystem\Driver\File $fileDrive
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepository
     * @param RedisHelper $redisHelper
     * @param LoggerInterface $logger
     * @param ModuleListInterface $moduleList
     * @param ResourceInterface $moduleResource
     * @param Store $store
     * @param WriterInterface $configWriter
     * @param VarnishHelper $varnishHelper
     * @param ScopeConfigInterface $scopeConfig
     **/
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        \NitroPack\NitroPack\Model\NitroPackEvent\Trigger $trigger,
        \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepo,
        \NitroPack\NitroPack\Helper\ApiHelper $apiHelper,
        \Magento\Framework\Filesystem $filesystem,
        DirectoryList $directoryList,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Filesystem\Driver\File $fileDrive,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Store\Api\StoreRepositoryInterface $storeRepository,
        ModuleListInterface $moduleList,
        ResourceInterface $moduleResource,
        RedisHelper $redisHelper,
        LoggerInterface $logger,
        VarnishHelper $varnishHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        Store $store
    ) {
        $this->storeGroupRepo = $storeGroupRepo;
        $this->trigger = $trigger;
        $this->moduleList = $moduleList;
        $this->logger = $logger;
        $this->redisHelper = $redisHelper;
        $this->store = $store;
        $this->storeManager = $storeManager;
        $this->fileDrive = $fileDrive;
        $this->directoryList = $directoryList;
        $this->serializer = $serializer;
        $this->fileSystem = $filesystem;
        $this->storeRepository = $storeRepository;
        $this->apiHelper = $apiHelper;
        $this->moduleResource = $moduleResource;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->varnishHelper = $varnishHelper;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $this->moduleDataSetup->getConnection()->startSetup();
        //Legacy Migration Script

        $this->migrationScript();

        $this->trigger->hitEvent('update', false);

        $this->moduleDataSetup->getConnection()->endSetup();

        $this->moduleDataSetup->endSetup();
    }

    public function setData($path, $value)
    {
        $this->configWriter->save($path, $value, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
    }

    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        //TRIGGER AN EVENT OF UNINSTALL
        $this->trigger->hitEvent('uninstall', false);
        if ($this->scopeConfig->getValue('system/full_page_cache/varnish_enable')) {
            $this->setData('system/full_page_cache/caching_application', \Magento\PageCache\Model\Config::VARNISH);
        } else {
            $this->setData('system/full_page_cache/caching_application', \Magento\PageCache\Model\Config::BUILT_IN);
        }
        $this->varnishHelper->purgeVarnish();

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public static function getVersion()
    {
        return NitroService::EXTENSION_VERSION;
    }


    public function migrationScript()
    {
        $storeGroup = $this->storeGroupRepo->getList();
        $storeViewCode = [];
        foreach ($storeGroup as $storesData) {
            //Check Have Multiple STore view under Store group
            $haveMigrated = false;
            if (count($storesData->getStores()) > 0) {
                //Current Url
                $currentUrl = $this->currentStoreGroupUrlForStoreView($storesData);
                $defaultStoreView = $this->storeManager->getStore($storesData->getDefaultStoreId());
                //Check When Have NitroPack Connection Previously Connected
                $haveDefaultStoreViewConnected = false;
                $haveNitroPackConnection = false;
                $rootPath = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR;
                foreach ($storesData->getStores() as $storeGroupView) {
                    //OlD File name before store group Implementation
                    $settingNameOld = 'nitro_settings_' . $storeGroupView->getCode() . '.json';

                    $settingNameOldRead = $this->apiHelper->getSettingsFilename($storeGroupView->getCode());

                    //Var Directory Where Nitropack Store Config file is stored
                    $dir = $this->fileSystem->getDirectoryWrite(DirectoryListApp::VAR_DIR);
                    //File is readable
                    if ($this->apiHelper->readFile($settingNameOldRead)) {
                        $settingNameNew = 'nitro_settings_' . $storesData->getCode() . '.json';
                        //Rename All Store View file into store group file
                        $settingData = $this->serializer->unserialize($this->apiHelper->readFile($settingNameOldRead));
                        //Remove The Local Cache If Required
                        if (isset($settingData['siteId']) && $settingData['siteId']) {
                            //Check We need to rename and Resubmission due to difference of URL


                            if ($this->checkUrl($currentUrl, $settingData['siteId'], $settingData['siteSecret'])) {
                                $haveMigrated = true;
                                $dir->renameFile($settingNameOld, $settingNameNew, $dir);
                                if ($defaultStoreView->getCode() == $storeGroupView->getCode()) {
                                    $haveDefaultStoreViewConnected = true;
                                }
                            } else {
                                $this->fileDrive->deleteFile($rootPath . $settingNameOld);
                                $haveNitroPackConnection = true;
                            }

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
                            if (!\NitroPack\SDK\Filesystem::isDirEmpty(
                                $rootPath . 'nitro_cache/' . $settingData['siteId']
                            )) {
                                \NitroPack\SDK\Filesystem::deleteDir(
                                    $rootPath . 'nitro_cache/' . $settingData['siteId']
                                );
                            }
                        }
                    }
                    $storeViewCode[] = $storeGroupView->getCode(); // get store view name
                }
                $this->disconnectNotify(
                    $haveDefaultStoreViewConnected,
                    $haveNitroPackConnection,
                    $rootPath,
                    $storesData
                );
                //Set Multiple Store View as Variation Cookie
                if (!$this->store->isUseStoreInUrl() && $haveMigrated) {
                    $this->setVariationIfRequired($storesData, $storeViewCode);
                }
            }
        }
    }

    /**
     * @param $storeView
     * @return void
     */
    public function currentStoreGroupUrlForStoreView($storeData)
    {
        $defaultStoreView = $this->storeManager->getStore($storeData->getDefaultStoreId());
        return $this->store->isUseStoreInUrl() ? str_replace(
            $defaultStoreView->getCode() . '/',
            '',
            $defaultStoreView->getBaseUrl()
        ) : $defaultStoreView->getBaseUrl(); // get store view name

    }

    /**
     * @param \Magento\Store\Api\Data\GroupInterface $storesData
     * @param array $storeViewCode
     * @return void
     */
    public function setVariationIfRequired(
        \Magento\Store\Api\Data\GroupInterface $storesData,
        array $storeViewCode
    ): void {
        $storeDataFileName = $this->apiHelper->getSettingsFilename($storesData->getCode());
        if ($this->apiHelper->readFile($storeDataFileName)) {
            $storeDataFile = $this->serializer->unserialize(
                $this->apiHelper->readFile($storeDataFileName)
            );
            if (isset($storeDataFile['siteId']) && $storeDataFile['siteId'] && isset($storeDataFile['siteSecret']) && $storeDataFile['siteSecret']) {
                foreach ($storeViewCode as $storeViewCodeValue) {
                    $store = $this->storeRepository->get($storeViewCodeValue);
                    if ($storesData->getDefaultStoreId() != $store->getId()) {
                        try {
                            $nitroPackSdk = new \NitroPack\SDK\NitroPack(
                                $storeDataFile['siteId'],
                                $storeDataFile['siteSecret'],
                                null, null,
                                $this->directoryList->getPath(
                                    'var'
                                ) . DIRECTORY_SEPARATOR . 'nitro_cache' . DIRECTORY_SEPARATOR . $storeDataFile['siteId']
                            );
                            if (!is_null($nitroPackSdk)) {
                                $nitroPackSdk->getApi()->setVariationCookie('store', $storeViewCode);
                            }
                        } catch (\Exception $e) {
                            $this->logger->info($e->getMessage());
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $url
     * @param $siteId
     * @param $siteSecret
     * @return  bool
     * */
    public function checkUrl($url, $siteId, $siteSecret)
    {
        $rootPath = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR;
        $cachePath = $rootPath . 'nitro_cache' . DIRECTORY_SEPARATOR . $siteId;
        try {
            $nitroPackSdk = new \NitroPack\SDK\NitroPack(
                $siteId,
                $siteSecret,
                null,
                $url,
                $cachePath
            );

            if (!is_null($nitroPackSdk)) {
                $response = $nitroPackSdk->getApi()->getCache($url, 'magento-cli', [], false, '');

                if ($response->getStatus() == \NitroPack\SDK\Api\ResponseStatus::OK || $response->getStatus(
                    ) == \NitroPack\SDK\Api\ResponseStatus::ACCEPTED) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            return false;
        }
    }

    /**
     * @param $siteId
     * @param $siteSecret
     * */
    public function purgeNitroPack($siteId, $siteSecret)
    {
        try {
            $nitroPackSdk = new \NitroPack\SDK\NitroPack(
                $siteId,
                $siteSecret,
                null, null,
                $this->directoryList->getPath(
                    'var'
                ) . DIRECTORY_SEPARATOR . 'nitro_cache' . DIRECTORY_SEPARATOR . $siteId
            );
            $nitroPackSdk->purgeCache(
                null,
                null,
                \NitroPack\SDK\PurgeType::COMPLETE,
                "Magento cache flush by migration script remove all page cache"
            );
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            return false;
        }
    }

    /**
     * @param bool $haveDefaultStoreViewConnected
     * @param bool $haveNitroPackConnection
     * @param string $rootPath
     * @param \Magento\Store\Api\Data\GroupInterface $storesData
     * @return array|bool|float|int|string|null
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function disconnectNotify(
        bool $haveDefaultStoreViewConnected,
        bool $haveNitroPackConnection,
        string $rootPath,
        \Magento\Store\Api\Data\GroupInterface $storesData
    ) {
        if (!$haveDefaultStoreViewConnected && $haveNitroPackConnection) {
            //Notify User To Connect again
            if ($this->fileDrive->isWritable($rootPath)) {
                $nitroNotifyContent = [];
                if ($this->fileDrive->isExists($rootPath . 'notify_disconnection' . '.json')) {
                    $nitroPackConnectionData = $this->serializer->unserialize(
                        $this->fileDrive->fileGetContents($rootPath . 'notify_disconnection' . '.json')
                    );
                    $nitroPackConnectionSet = false;
                    foreach ($nitroPackConnectionData as $nitroPackConnectionDataValue) {
                        if (isset($nitroPackConnectionDataValue['store_group_code']) && $nitroPackConnectionDataValue['store_group_code'] == $storesData->getCode(
                            )) {
                            $nitroPackConnectionSet = true;
                            break;
                        }
                    }
                    if (!$nitroPackConnectionSet) {
                        array_push(
                            $nitroPackConnectionData,
                            ['store_group_code' => $storesData->getCode(), 'created_at' => time()]
                        );
                    }
                    $this->fileDrive->filePutContents(
                        $rootPath . 'notify_disconnection' . '.json',
                        $this->serializer->serialize(
                            $nitroPackConnectionData
                        )
                    );
                } else {
                    $this->fileDrive->filePutContents(
                        $rootPath . 'notify_disconnection' . '.json',
                        $this->serializer->serialize(
                            [['store_group_code' => $storesData->getCode(), 'created_at' => time()]]
                        )
                    );
                }
            }
        }
    }
}
