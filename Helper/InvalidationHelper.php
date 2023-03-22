<?php

namespace NitroPack\NitroPack\Helper;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use \Magento\Framework\Shell;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroService;

use Magento\Framework\Filesystem\DirectoryList;


class InvalidationHelper extends AbstractHelper
{
    /**
     * @var Shell $shell
     * */
    protected $shell;
    /**
     * @var \Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory $cronFactory
     * */
    protected $cronFactory;

    /**
     * @var ApiHelper
     * */
    private $apiHelper;

    private $stores = null;
    /**
     * @var ScopeConfigInterface
     * */
    protected $_scopeConfig;
    /**
     * @var StateInterface
     * */
    protected $_cacheState;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     * */
    protected $fileDriver;
    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     * */
    protected $serializer;
    protected $settings;
    private $cacheTtl = 259200;
    /**
     * @var DirectoryList
     * */
    protected $directoryList;
    /**
     * @var \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepository
     */
    protected $storeGroupRepo;
    /**
     * @var UrlInterface
     * */
    protected $_backendUrl;
    /**
     *
     * @param  Context $context
     * @param  Shell $shell
     * @param  \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepo
     * @param DirectoryList $directoryList
     * @param \Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory $cronFactory
     * @param ApiHelper $apiHelper
     * @param \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepository
     * @param ScopeConfigInterface $_scopeConfig
     * @param StateInterface $_cacheState
     * @param \Magento\Framework\Filesystem\Driver\File $fileDriver
     * @param UrlInterface $backendUrl
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * */

    public function __construct(
        Context $context,
        Shell $shell,
        \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepo,
        DirectoryList $directoryList,
        \Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory $cronFactory,
        ApiHelper $apiHelper,
        \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepository,
        ScopeConfigInterface $_scopeConfig,
        StateInterface $_cacheState,
        \Magento\Framework\Filesystem\Driver\File $fileDriver,
        UrlInterface $backendUrl, // dependency injection'ed
        \Magento\Framework\Serialize\SerializerInterface $serializer
    ) {
        $this->directoryList = $directoryList;
        $this->serializer = $serializer;
        $this->fileDriver = $fileDriver;
        $this->cronFactory = $cronFactory;
        $this->shell = $shell;
        $this->_backendUrl = $backendUrl;
        $this->apiHelper = $apiHelper;
        $this->_scopeConfig = $_scopeConfig;
        $this->_cacheState = $_cacheState;
        $this->storeGroupRepo = $storeGroupRepository;
        /**
         * @var \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepository
         */
        $this->stores = $storeGroupRepository->getList();

        parent::__construct($context);
    }

    /**
     * Check consumers is running
     */
    public function checkInvalidationAndPurgeProcess()
    {
        try {
            $output = $this->shell->execute("ps -C php -f", []);
        } catch (\Exception $exception) {
            $output = "";
        }
        if (strpos($output, "php bin/magento queue:consumers:start nitropack.cache.queue.consumer") !== false) {
            return true;
        }
        return false;
    }

    function checkCronJobIsSetup()
    {
        $crontabCollection = $this->cronFactory->create()->addFieldToSelect('executed_at')->addFieldToFilter(
            'job_code',
            'nitropack_cron_health_status',
            'eq'
        )
            ->addFieldToFilter('executed_at', array('notnull' => true))
            ->setOrder('executed_at', 'DESC')->setPageSize(3);
        $cronSetup = false;
        foreach ($crontabCollection->getData() as $crontabCollectionValue) {
            $to_time = strtotime($crontabCollectionValue['executed_at']);
            $from_time = time();
            if (round(abs($to_time - $from_time) / $this->cacheTtl, 2) <= 10) {
                $cronSetup = true;
            }
        }
        return $cronSetup;
    }

    function makeConnectionsDisableAndEnable($serviceEnable)
    {
        $this->settings = null;
        if (!is_null(
                $this->_scopeConfig->getValue('system/full_page_cache/caching_application')
            ) && $this->_scopeConfig->getValue(
                'system/full_page_cache/caching_application'
            ) == NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE && $this->_cacheState->isEnabled('full_page')) {
            $this->setEnableAndDisable($serviceEnable);
        }
    }

    function setEnableAndDisable($serviceEnable)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get(StoreManagerInterface::class);
        $storeRepo = $objectManager->create(\Magento\Store\Api\GroupRepositoryInterface::class);
        $stores = $storeRepo->getList();
        foreach ($stores as $storesData) {
            $settingsFilename = $this->apiHelper->getSettingsFilename($storesData->getCode());
            $haveData = $this->apiHelper->readFile($settingsFilename);
            //Check The file is readable
            if ($haveData) {
                $this->settings = json_decode($haveData);
                if (isset($this->settings->enabled)) {
                    $triggerEnabled=false;
                    if( $serviceEnable!=$this->settings->enabled){
                        $triggerEnabled = true;
                    }
                    if (isset($this->settings->previous_extension_status) && !$this->settings->previous_extension_status && $serviceEnable) {
                        $this->settings->enabled = false;
                    } else {
                        $this->settings->enabled = $serviceEnable;
                    }
                    if($triggerEnabled){

                    $this->apiHelper->triggerEventMultipleStore(
                        $serviceEnable ? 'enable_extension' : 'disable_extension',
                        false,
                        $storesData,
                        $this->settings
                    );
                    }
                    $this->fileDriver->filePutContents(
                        $settingsFilename,
                        $this->serializer->serialize($this->settings)
                    );
                }
            }
        }
    }

    public function checkNotificationLegacyDisconnect()
    {
        $rootPath = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR;
        if ($this->fileDriver->isExists($rootPath . 'notify_disconnection' . '.json')) {
            $data = $this->serializer->unserialize(
                $this->fileDriver->fileGetContents($rootPath . 'notify_disconnection' . '.json')
            );
            foreach ($data as $value) {
                if ($value['created_at'] + $this->cacheTtl > time()) { // The cache is still fresh
                    foreach ($this->stores as $storeGroupValue) {
                        if ($storeGroupValue->getCode() == $value['store_group_code']) {
                            return $storeGroupValue->getId();
                        }
                    }
                    return false;
                }
            }
            return false;
        }
        return false;
    }

    public function backUrlRedirectToConnect($id)
    {
        return $this->_backendUrl->getUrl('NitroPack/connect/index', ['group' => $id]);
    }

    public function checkHavePreviouslyConnected()
    {
        $rootPath = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR;
        if ($this->fileDriver->isDirectory($rootPath) && $this->fileDriver->isWritable($rootPath)) {
            $paths = $this->fileDriver->readDirectory($rootPath);
            $checkHaveConnectedFile = preg_grep('/nitro_settings_(\w+)/', $paths);
            if (count($checkHaveConnectedFile) > 0) {
                return true;
            }
        }
        return false;
    }
}
