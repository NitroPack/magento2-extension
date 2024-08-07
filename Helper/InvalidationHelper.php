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
namespace NitroPack\NitroPack\Helper;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use \Magento\Framework\Shell;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroService;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Filesystem\DirectoryList;
use NitroPack\NitroPack\Helper\FastlyHelper;
use Magento\Framework\App\RequestInterface;

/**
 * Class InvalidationHelper - Cover Different Functionality for Invalidation, Cron Checker and Disable Extension When Cron isn't running
 * @extends AbstractHelper
 * @package NitroPack\NitroPack\Helper
 * @since 2.0.0
 */
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
    private $cacheTtl = 7200;
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
     * @var WriterInterface
     * */
    protected $configWriter;
    protected static $cachesToEnable = 'full_page';
    /**
     * @var StoreManagerInterface
     * */
    protected $storeManager;
    /**
     * @var TypeListInterface
     * */
    protected $cacheTypeList;
    /**
     * @var NitroPackConfigHelper
     * */
    protected $nitroHelper;
    /**
     * @var \Magento\Framework\App\Cache\Frontend\Pool
     * */
    protected $cacheFrontendPool;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     *
     * @param Context $context
     * @param Shell $shell
     * @param DirectoryList $directoryList
     * @param \Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory $cronFactory
     * @param ApiHelper $apiHelper
     * @param \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepository
     * @param ScopeConfigInterface $_scopeConfig
     * @param StateInterface $_cacheState
     * @param \Magento\Framework\Filesystem\Driver\File $fileDriver
     * @param UrlInterface $backendUrl
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param NitroPackConfigHelper $nitroHelper
     * @param WriterInterface $configWriter
     * @param StoreManagerInterface $storeManager
     * @param TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
     * */

    public function __construct(
        Context                                                      $context,
        Shell                                                        $shell,
        DirectoryList                                                $directoryList,
        \Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory $cronFactory,
        ApiHelper                                                    $apiHelper,
        \Magento\Store\Api\GroupRepositoryInterface                  $storeGroupRepository,
        ScopeConfigInterface                                         $_scopeConfig,
        StateInterface                                               $_cacheState,
        \Magento\Framework\Filesystem\Driver\File                    $fileDriver,
        UrlInterface                                                 $backendUrl, // dependency injection'ed
        \Magento\Framework\Serialize\SerializerInterface             $serializer,
        NitroPackConfigHelper                                        $nitroHelper,
        WriterInterface                                              $configWriter,
        StoreManagerInterface                                        $storeManager,
        TypeListInterface                                            $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool                   $cacheFrontendPool,
        RequestInterface                                             $request
    )
    {
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->storeManager = $storeManager;
        $this->cacheTypeList = $cacheTypeList;
        $this->nitroHelper = $nitroHelper;
        $this->configWriter = $configWriter;
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
        $this->request = $request;
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

        if (strpos($output, "queue:consumers:start nitropack.cache.queue.consumer") !== false) {
            return true;
        }
        return false;
    }

    function checkCronJobIsSetup($defineTime = null)
    {

        $crontabCollection = $this->cronFactory->create()->addFieldToSelect('executed_at')->addFieldToFilter(
            'job_code',
            array('in' => array('nitropack_consumers_runner', 'nitropack_cron_for_health_and_stale_cleanup')
            )
        )
            ->addFieldToFilter('executed_at', array('notnull' => true))
            ->setOrder('executed_at', 'DESC')->setPageSize(3);
        $cronSetup = false;
        foreach ($crontabCollection->getData() as $crontabCollectionValue) {
            $to_time = strtotime($crontabCollectionValue['executed_at']);
            $from_time = time();
            $defineTime = !is_null($defineTime) ? $defineTime : $this->cacheTtl;
            if (round(abs($to_time - $from_time) / 60, 2) <= $defineTime) {
                $cronSetup = true;
            }
        }
        return $cronSetup;
    }


    public function makeConnectionsDisableAndEnable($serviceEnable)
    {
        $this->settings = null;
        $stores = $this->stores;
        $triggerEnabled = true;
        foreach ($stores as $storesData) {
            $settingsFilename = $this->apiHelper->getSettingsFilename($storesData->getCode());
            $haveData = $this->apiHelper->readFile($settingsFilename);
            if ($haveData) {
                $settings = json_decode($haveData);
                if (isset($settings->previous_extension_status) && !$settings->previous_extension_status) {
                    $triggerEnabled = false;
                    $serviceEnable = false;
                } else {
                    $triggerEnabled = true;
                }

            }
        }

        if ($triggerEnabled) {
            $this->cacheApplicationChange($serviceEnable);
        }
         $this->setEnableAndDisable($serviceEnable);

    }

    public function setEnableAndDisable($serviceEnable)
    {
        foreach ($this->stores as $storesData) {
            $settingsFilename = $this->apiHelper->getSettingsFilename($storesData->getCode());
            $haveData = $this->apiHelper->readFile($settingsFilename);
            //Check The file is readable
            if ($haveData) {
                $this->settings = json_decode($haveData);
                if (isset($this->settings->enabled)) {
                    $triggerEnabled = false;
                    if ($serviceEnable != $this->settings->enabled) {
                        $triggerEnabled = true;
                    }
                    if (isset($this->settings->previous_extension_status) && !$this->settings->previous_extension_status && !$serviceEnable) {
                        $this->settings->enabled = false;
                    } else {
                        $this->settings->enabled = $serviceEnable;
                    }
                    if ($triggerEnabled) {
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

    public function checkHavePreviouslyConnected($checkConnectedSafeMode = false)
    {

        $rootPath = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR;
        if ($this->fileDriver->isDirectory($rootPath) && $this->fileDriver->isWritable($rootPath)) {
            $paths = $this->fileDriver->readDirectory($rootPath);
            $checkHaveConnectedFileArray = preg_grep('/nitro_settings_(\w+)/', $paths);

            if (count($checkHaveConnectedFileArray) > 0) {
                $enabledFlag = true;
                $checkSafeMode = false;
                foreach ($checkHaveConnectedFileArray as $checkHaveConnectedFile) {
                    $data = json_decode($this->fileDriver->fileGetContents($checkHaveConnectedFile));

                    if (!$data->enabled) {
                        $enabledFlag = false;
                    } else {
                        $enabledFlag = true;
                    }
                    if ($checkConnectedSafeMode) {

                        if (isset($data->safeMode) && $data->safeMode) {
                            $checkSafeMode = true;
                        } else {
                            $checkSafeMode = false;
                        }

                    }
                }

                if($checkConnectedSafeMode && $enabledFlag){
                    return $checkSafeMode;
                }
                return $enabledFlag;
            }
        }
        return false;
    }


    public function cacheApplicationChange($serviceEnable)
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
        try {
            $this->cacheTypeList->cleanType(self::$cachesToEnable);
            if (!$this->_cacheState->isEnabled(self::$cachesToEnable)) {
                $this->_cacheState->setEnabled(self::$cachesToEnable, true);
            }

            if ($serviceEnable) {

                if ($this->scopeConfig->getValue(NitroService::FULL_PAGE_CACHE_NITROPACK) != $this->scopeConfig->getValue('full_page_cache/fields/caching_application/value')) {

                    $this->configWriter->save(NitroService::FULL_PAGE_CACHE_NITROPACK, $this->scopeConfig->getValue('full_page_cache/fields/caching_application/value'), ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);

                    if ($this->nitroHelper->isVarnishConfigured($baseUrl)) {
                        $this->configWriter->save(NitroService::XML_VARNISH_PAGECACHE_NITRO_ENABLED, 1, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                    }

                }
                if ($this->scopeConfig->getValue(NitroService::FULL_PAGE_CACHE_NITROPACK) == NitroService::FASTLY_CACHING_APPLICATION_VALUE && $this->scopeConfig->getValue(FastlyHelper::XML_PATH_CACHING_APPLICATION_IS_NITROPACK_ENABLED) == 0) {
                    $this->configWriter->save(FastlyHelper::XML_PATH_CACHING_APPLICATION_IS_NITROPACK_ENABLED, 1, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                }
            } else {
                if ($this->scopeConfig->getValue(NitroService::FULL_PAGE_CACHE_NITROPACK) == FastlyHelper::FASTLY_CACHING_APPLICATION_VALUE) {
                    $this->configWriter->save(FastlyHelper::XML_PATH_CACHING_APPLICATION_IS_NITROPACK_ENABLED, 0, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                } elseif ($this->nitroHelper->isVarnishConfigured($baseUrl)) {
                    $this->configWriter->save(NitroService::FULL_PAGE_CACHE_NITROPACK, 2, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                } else {
                    $this->configWriter->save(NitroService::FULL_PAGE_CACHE_NITROPACK, 1, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                }
            }
            $types = array_keys($this->cacheTypeList->getTypes());
            foreach ($types as $type) {
                $this->cacheFrontendPool->get($type)->getBackend()->clean();
            }
            $this->_cacheState->persist();
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
        }
    }
}
