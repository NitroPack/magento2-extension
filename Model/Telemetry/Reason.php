<?php

namespace NitroPack\NitroPack\Model\Telemetry;

use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use Magento\Framework\ObjectManagerInterface;


class Reason
{
    /**
     * @var RequestInterface
     */
    protected $request;
    /**
     * @var ResponseInterface
     */
    protected $response;

    protected $reason = 'Undefined Reason';

    protected $reasonHave = [
        'not_connected' => "Nitropack is not connected",
        'disabled' => "Nitropack is not Enabled",
        'sick' => "Nitropack is not Health status is SICK",
        'remote_cache_missed' => "Remote Cache is not created",
        'local_cache_missed' => "Local Server Cache is not created",
        'customer_logged_in' => "Customer is logged in",
        'no_cacheable' => "No Cacheable in magento layout",
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
        ResponseInterface $response,
        ObjectManagerInterface $objectManager
    ) {
        $this->storeManager = $storeManager;
        $this->objectManager = $objectManager;
        $this->nitro = $nitro;
        $this->request = $request;
        $this->response = $response;
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
        if(strpos($this->request->getRequestUri(), 'checkout') !== false){
            $this->setReason("Checkout Page");
            return true;
        }

        if(strpos($this->response->getHeader('Cache-Control')->getFieldValue(),'no-cache')!==false){

            $this->setReason($this->reasonHave['no_cacheable']);
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

}
