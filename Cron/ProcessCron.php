<?php

namespace NitroPack\NitroPack\Cron;

use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Helper\RedisHelper;
use Psr\Log\LoggerInterface;

class ProcessCron
{
    /**
     * @var NitroServiceInterface $nitro
     * */
    protected $nitro;
    /**
     * @var  \Magento\Framework\Filesystem\Driver\File $directoryList
     * */
    protected $directoryList;
    /**
     * @var StoreManagerInterface $storeManager
     * */
    protected $storeManager;
    /**
     * @var RedisHelper $redisHelper
     * */
    protected $redisHelper;
    /**
     * @var LoggerInterface $logger
     * */
    protected $logger;
    /**
     * @var \Magento\Store\Api\GroupRepositoryInterface
     * */
    protected $storeRepo;
    /**
     * @param NitroServiceInterface $nitro
     * @param StoreManagerInterface $storeManager
     * @param RedisHelper $redisHelper
     * @param LoggerInterface $logger
     * @param \Magento\Store\Api\GroupRepositoryInterface $storeRepo
     * */
    public function __construct(
        NitroServiceInterface $nitro,
        StoreManagerInterface $storeManager,
        RedisHelper           $redisHelper,
        LoggerInterface       $logger,
        \Magento\Store\Api\GroupRepositoryInterface $storeRepo
    )
    {
        $this->logger = $logger;
        $this->nitro = $nitro;
        $this->redisHelper = $redisHelper;
        $this->storeRepo = $storeRepo;
        $this->storeManager = $storeManager;
    }
    /**
     * @return $this NitroPack\NitroPack\Cron\ProcessCron
     * */
    public function execute()
    {

        $storeGroup = $this->storeRepo->getList();
        foreach ($storeGroup as $storesData) {
            try {
                $this->nitro->reload($storesData->getCode());
                if ($this->nitro->isConnected()) {
                    //HEALTH CHECK
                    $this->nitro->checkHealthStatus();
                    $this->nitro->getSdk()->backlog->replay();
                    // clean up the stale Cache for filesystem and redis If Configure
                    $this->cleanupStaleCache();
                }
            } catch (\Exception $e) {
                $this->logger->info($e->getMessage());
            }
        }
        return $this;
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

        $cacheDirectoryForStale = str_replace('/pagecache', '', $this->nitro->getSdk()->getCacheDir());
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
}
