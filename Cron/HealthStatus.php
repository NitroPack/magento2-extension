<?php

namespace NitroPack\NitroPack\Cron;

use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use Psr\Log\LoggerInterface;

class HealthStatus
{
    /**
     * @var NitroServiceInterface
     * */
    protected $nitro;
    /**
     * @var StoreManagerInterface
     * */
    protected $storeManager;
    /**
     * @var LoggerInterface
     * */
    protected $logger;

    /**
     * @param NitroServiceInterface $nitro
     * @param LoggerInterface $logger
     * */
    public function __construct(
        NitroServiceInterface $nitro,
        LoggerInterface $logger
    ) {
        $this->nitro = $nitro;
        $this->logger = $logger;
    }

    public function execute()
    {
        //Load NitroPack Library to set credential
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeRepo = $objectManager->create(\Magento\Store\Api\GroupRepositoryInterface::class);
        $storeGroup = $storeRepo->getList();
        foreach ($storeGroup as $storesData) {
            try {
                $this->nitro->reload($storesData->getCode());
                if ($this->nitro->isConnected()) {
                    $this->nitro->checkHealthStatus();
                }
            } catch (\Exception $e) {
                $this->logger->info($storesData->getCode() . ' ' . $e->getMessage());
            }
        }
        return $this;
    }
}
