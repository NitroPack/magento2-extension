<?php

namespace NitroPack\NitroPack\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use NitroPack\NitroPack\Logger\Logger;

class StoreViewObserver implements ObserverInterface
{
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var \NitroPack\NitroPack\Helper\NitroPackConfigHelper
     * */
    private $nitroPackConfigHelper;

    public function __construct(Logger $logger, \NitroPack\NitroPack\Helper\NitroPackConfigHelper $nitroPackConfigHelper)
    {
        $this->nitroPackConfigHelper = $nitroPackConfigHelper;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeRepo = $objectManager->create(\Magento\Store\Api\GroupRepositoryInterface::class);
        $storeGroup = $storeRepo->getList();
        foreach ($storeGroup as $storeGroupData) {
            $storeGroupCode = $storeGroupData->getCode();

            $this->nitroPackConfigHelper->xMagentoVaryAdd($storeGroupData);
        }
    }


}
