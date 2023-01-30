<?php

namespace NitroPack\NitroPack\Model\Telemetry;

use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use Magento\Framework\ObjectManagerInterface;


class Reason
{
    /**
     * @var RequestInterface
     */
    protected $request;

    protected $reason = 'Undefined Reason';

    protected $reasonHave = [
        'not_connected' => "Nitropack is not connected",
        'disabled' => "Nitropack is not Enabled",
        'sick' => "Nitropack is not Health status is SICK",
        'remote_cache_missed' => "Remote Cache is not created",
        'local_cache_missed' => "Local Server Cache is not created",
        'customer_logged_in' => "Customer is logged in",
        'have_cart_item' => "Have Cart Item"
    ];
    /**
     * @var NitroServiceInterface
     * */
    private $nitro;

    /**
     * @var StoreManagerInterface
     * */
    private $storeManager;
    /**
     * @var ObjectManagerInterface
     * */
    private $objectManager;
    private $context = null;
    private $cart = null;

    public function __construct(
        NitroServiceInterface $nitro,
        StoreManagerInterface $storeManager,
        RequestInterface $request,
        ObjectManagerInterface $objectManager
    ) {
        $this->storeManager = $storeManager;
        $this->objectManager = $objectManager;
        $this->nitro = $nitro;
        $this->request = $request;
    }

    public function possibleReason()
    {
        if (!$this->nitro->isConnected()) {
            $this->setReason($this->reasonHave['not_connected']);
            return true;
        }

        if (!$this->nitro->isEnabled()) {
            $this->setReason($this->reasonHave['disabled']);
            return true;
        }
        if (!is_null($this->nitro->getSdk())) {
            if ($this->nitro->getSdk()->getHealthStatus() == "SICK") {
                $this->setReason($this->reasonHave['sick']);
                return true;
            }
            if($this->checkUserLogin()){
                $this->setReason($this->reasonHave['customer_logged_in']);
                return true;
            }

            if($this->checkHaveItemInCart()){
                $this->setReason($this->reasonHave['have_cart_item']);
                return true;
            }
        if (!$this->nitro->hasLocalCache()) {
            $this->setReason($this->reasonHave['local_cache_missed']);

            return true;
        }
        if ($this->checkHaveRemoteCache()) {
            $this->setReason($this->reasonHave['remote_cache_missed']);
            return true;
        }


        } else {
            return false;
        }

        return false;
    }

    public function setReason($value)
    {
        $this->reason = $value;
    }

    public function getReason()
    {
        return $this->reason;
    }


    private function checkHaveRemoteCache()
    {
        $store = $this->storeManager->getStore();
        $storeViewId = $store->getId();
        $storeId = $store->getStoreGroupId();
        $websiteId = $store->getWebsiteId();
        $route = $this->request->getFullActionName();

        $layout = $websiteId . '_' . $storeId . '_' . $storeViewId . '_' . $route;
        if (!$this->nitro->hasRemoteCache($layout)) {
            return true;
        }
        return false;
    }

    private function checkUserLogin()
    {
        if (!$this->context) {
            $this->context = $this->objectManager->get(\Magento\Framework\App\Http\Context::class);
        }

        if ($this->context->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH)) {
            return true;
        }
        return false;
    }


    private function checkHaveItemInCart()
    {
        if (!$this->cart) {
            $this->cart = $this->objectManager->get(Cart::class);
        }
        if (!empty($this->cart->getQuote()->getAllItems())) {
            return true;
        }
        return false;
    }
}
