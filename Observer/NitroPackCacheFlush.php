<?php

namespace NitroPack\NitroPack\Observer;


use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Filesystem\DirectoryList;
use NitroPack\NitroPack\Helper\ApiHelper;
use NitroPack\NitroPack\Helper\RedisHelper;
use NitroPack\NitroPack\Model\FullPageCache\PurgeInterface;
use NitroPack\SDK\NitroPack;
use Magento\Cron\Model\Config;
use Magento\Cron\Model\Schedule;
use NitroPack\NitroPack\Logger\Logger;

class NitroPackCacheFlush implements ObserverInterface
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
     * @var Config
     * */
    protected $cronConfig;
    /**
     * @var Schedule
     * */
    protected $cronSchedule;
    /**
     * @var \Magento\Store\Api\GroupRepositoryInterface
     * */
    protected $storeGroupRepo;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     * */
    public $fileDriver;

    protected $logger;
    /**
     * @param DirectoryList $directoryList
     * @param ApiHelper $apiHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param PurgeInterface $purgeInterface
     * @param RedisHelper $redisHelper
     * @param Config $cronConfig
     * @param \Magento\Framework\Filesystem\Driver\File $fileDriver
     * @param \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepo
     * @param Schedule $cronSchedule
     * */
    public function __construct(
        DirectoryList $directoryList,
        ApiHelper $apiHelper,
        PurgeInterface $purgeInterface,
        ScopeConfigInterface $scopeConfig,
        RedisHelper $redisHelper,
        Config $cronConfig,
        \Magento\Framework\Filesystem\Driver\File $fileDriver,
        \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepo,
        Schedule $cronSchedule,
        Logger $logger

    ) {

        $this->fileDriver = $fileDriver;
        $this->cronSchedule =  $cronSchedule;
        $this->redisHelper =$redisHelper;
        $this->apiHelper = $apiHelper;
        $this->purgeInterface = $purgeInterface;
        $this->_scopeConfig = $scopeConfig;
        $this->directoryList = $directoryList;
        $this->cronConfig = $cronConfig;
        $this->storeGroupRepo = $storeGroupRepo;
        $this->logger = $logger;
   }

    public function execute(Observer $observer)
    {

        $storeGroup = $this->storeGroupRepo->getList();

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
                    $this->sdk = new NitroPack(
                        $this->settings->siteId, $this->settings->siteSecret, null, null, $cachePath
                    );

                    if ($this->settings->enabled) {

                        $this->sdk->purgeCache(
                            null,
                            null,
                            \NitroPack\SDK\PurgeType::COMPLETE,
                            "Magento cache flush remove all page cache."
                        );
                        $this->purgeInterface->purge();
                    }

                        //HEALTH CHECK
                        $this->sdk->checkHealthStatus();
                        $this->sdk->backlog->replay();
                        // clean up the stale Cache for filesystem and redis If Configure
                        $this->cleanupStaleCache();
                        $this->runCronRecord();
                } catch (\Exception $e) {
                    $this->logger->error('SDK exception: ' . $e->getMessage());
                    $file = $this->fileDriver;
                    $cachePath = $rootPath . 'nitro_cache' . DIRECTORY_SEPARATOR . $this->settings->siteId;
                    if ($file->isDirectory($cachePath)) {
                        $file->deleteDirectory($cachePath);
                    }
                }
            }
        }
    }

    /**
     * @return bool Cleanup Local Stale cache directory
     * */
    public function cleanupStaleCache()
    {
        // Validate the Storage Driver
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

        $cacheDirectoryForStale = str_replace('/pagecache', '', $this->sdk->getCacheDir());
        if (!\NitroPack\SDK\Filesystem::getStorageDriver()->isDirEmpty($cacheDirectoryForStale)) {
            try {
                $dirs = array();
                $this->recursiveDirIteration($cacheDirectoryForStale, function ($entry) use (&$dirs) {
                    $in_dirs = false;

                    foreach ($dirs as $dir) {
                        if (stripos($entry, $dir) !== false) {
                            $in_dirs = true;
                            break;
                        }
                    }

                    if (stripos($entry, '.stale.') !== false && !\NitroPack\SDK\Filesystem::getStorageDriver()->isDirEmpty(
                            $entry
                        ) && !$in_dirs) {
                        $dirs[] = $entry;
                    }
                });

                foreach ($dirs as $dir) {
                    \NitroPack\SDK\Filesystem::getStorageDriver()->deleteDir($dir);
                }

                return !empty($dirs);
            } catch (\Magento\Framework\Exception\FileSystemException $e) {
                throw new \Exception($e->getMessage());
            }
        }
    }


    public static function recursiveDirIteration($dir, $callback)
    {
        \NitroPack\SDK\Filesystem::getStorageDriver()->dirForeach($dir, function ($entry) use (&$callback) {
            call_user_func($callback, $entry);

            if (!\NitroPack\SDK\Filesystem::getStorageDriver()->isDirEmpty($entry)) {
                self::recursiveDirIteration($entry, $callback);
            } else {
                if (stripos($entry, '.stale.') !== false && \NitroPack\SDK\Filesystem::getStorageDriver()->isDirEmpty(
                        $entry
                    )) {
                    \NitroPack\SDK\Filesystem::getStorageDriver()->deleteDir($entry);
                }
            }
        });
    }

    public function runCronRecord(){
        $specificJobCode = 'nitropack_cron_for_health_and_stale_cleanup'; // Replace with your job code
        $jobs = $this->cronConfig->getJobs();
        if (isset($jobs['default']) && isset($jobs['default'][$specificJobCode])) {

            $jobConfig = $jobs['default'][$specificJobCode];
            $this->cronSchedule->setJobCode($specificJobCode)
                ->setCreatedAt(date('Y-m-d H:i:s'))
                ->setScheduledAt(date('Y-m-d H:i:s'))
                ->setExecutedAt(date('Y-m-d H:i:s'))
                ->setStatus(Schedule::STATUS_SUCCESS)
                ->save();
        }
    }
}

