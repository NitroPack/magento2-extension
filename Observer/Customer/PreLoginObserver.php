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
namespace NitroPack\NitroPack\Observer\Customer;

use Magento\Framework\Event\ObserverInterface;
/**
 * Class PreLoginObserver - When a customer logs in, add the store group to the vary header
 * @implements ObserverInterface
 * @package NitroPack\NitroPack\Observer\Customer
 * @since 2.0.0
 * */
class PreLoginObserver implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     * */
    protected $_cookieManager;

    public function __construct(\Magento\Framework\Stdlib\Cookie\PhpCookieManager $cookieManager)
    {
        $this->_cookieManager = $cookieManager;

    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_cookieManager->deleteCookie(
            'PHPSESSID'
        );

    }
}
