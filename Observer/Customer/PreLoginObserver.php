<?php

namespace NitroPack\NitroPack\Observer\Customer;

use Magento\Framework\Event\ObserverInterface;

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
