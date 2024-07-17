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
namespace NitroPack\NitroPack\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use NitroPack\NitroPack\Logger\Logger;

/**
 * Class StoreViewObserver - Store View Observer
 * @implements ObserverInterface
 * @package NitroPack\NitroPack\Observer
 * @since 2.0.0
 * */
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
