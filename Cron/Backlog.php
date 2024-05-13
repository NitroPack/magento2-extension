<?php

namespace NitroPack\NitroPack\Cron;

use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Helper\RedisHelper;
use NitroPack\NitroPack\Logger\Logger;

/**
 * class Backlog
 */
class Backlog
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
     * @var \Magento\Store\Api\GroupRepositoryInterface
     * */
    protected $storeRepo;

    /**
     * @param NitroServiceInterface $nitro
     * @param StoreManagerInterface $storeManager
     * @param RedisHelper $redisHelper
     * @param Logger $logger
     * @param \Magento\Store\Api\GroupRepositoryInterface $storeRepo
     */
    public function __construct(
        NitroServiceInterface $nitro,
        StoreManagerInterface $storeManager,
        RedisHelper           $redisHelper,
        Logger                $logger,
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
     * @return $this
     */
    public function execute()
    {
        $storeGroup = $this->storeRepo->getList();
        foreach ($storeGroup as $storesData) {
            try {
                $this->nitro->reload($storesData->getCode());
                if ($this->nitro->isConnected()) {
                    if ($this->nitro->getSdk()->backlog->exists()) {
                        $this->logger->warning('BACKLOG CRON: PROCESSING BACKLOG.QUEUE');
                        $this->nitro->getSdk()->backlog->replay(30);
                        $this->logger->warning('BACKLOG CRON: FINISHED PROCESSING BACKLOG.QUEUE');
                    }
                }
            } catch (\Exception $e) {
                $this->logger->info('BACKLOG CRON:' . $e->getMessage());
            }
        }

        return $this;
    }
}
