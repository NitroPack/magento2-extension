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
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Helper\InvalidationHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class ProcessCronQueueObserver - Process Cron Queue Observer
 * @implements ObserverInterface
 * @package NitroPack\NitroPack\Observer
 * @since 2.0.0
 * */
class ProcessCronQueueObserver implements ObserverInterface
{
    /**
     * @var InvalidationHelper
     * */
    protected $invalidationHelper;

    protected $scopeConfig;

    public function __construct(
        InvalidationHelper $invalidationHelper,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->invalidationHelper = $invalidationHelper;
        $this->scopeConfig = $scopeConfig;
    }

    function execute(Observer $observer)
    {
        if ($this->scopeConfig->getValue(NitroService::FULL_PAGE_CACHE_NITROPACK) == NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE) {
            $this->invalidationHelper->makeConnectionsDisableAndEnable(true);
        }
    }
}
