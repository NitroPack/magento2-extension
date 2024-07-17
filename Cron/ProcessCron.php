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
namespace NitroPack\NitroPack\Cron;

use Exception;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Helper\RedisHelper;
use NitroPack\NitroPack\Logger\Logger;
/**
 * Class ProcessCron - Cron job to process the NitroPack backlog.queue and health check
 * @package NitroPack\NitroPack\Cron
 * @since 2.0.0
 */
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
     * @var Logger
     */
    protected $logger;
    /**
     * @var GroupRepositoryInterface
     * */
    protected $storeRepo;

    /**
     * @param NitroServiceInterface $nitro
     * @param StoreManagerInterface $storeManager
     * @param RedisHelper $redisHelper
     * @param Logger $logger
     * @param GroupRepositoryInterface $storeRepo
     */
    public function __construct(
        NitroServiceInterface $nitro,
        StoreManagerInterface $storeManager,
        RedisHelper           $redisHelper,
        Logger                $logger,
        GroupRepositoryInterface $storeRepo
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
                    $this->nitro->checkHealthStatus();
                    if ($this->nitro->getSdk()->backlog->exists()) {
                        $this->logger->info('Processing NitroPack backlog.queue');

                        $startTime = time();
                        $timeout = 280;

                        while (true) {
                            try {
                                $result = $this->nitro->getSdk()->backlog->replay();

                                if (time() - $startTime > $timeout) {
                                    break;
                                }

                                if ($result !== false) {
                                    break;
                                }

                            } catch (Exception $exception) {
                                $this->logger->error('Processing NitroPack backlog.queue:' . $exception->getMessage());
                                break;
                            }
                        }
                    }
                    $this->cleanupStaleCache();
                }
            } catch (Exception $exception) {
                $this->logger->info($exception->getMessage());
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
                throw new Exception($e->getMessage());
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
