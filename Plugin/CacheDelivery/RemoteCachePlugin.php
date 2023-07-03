<?php

namespace NitroPack\NitroPack\Plugin\CacheDelivery;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Observer\CacheTagObserver;

class RemoteCachePlugin
{
    // Checks if there is remote cache once the request has been routed, so we know the page type. Called after any RouterInterface instances' match() method.
    /**
     * @var NitroServiceInterface
     * */
    protected $nitro = null;
    /**
     * @var RequestInterface
     * */
    protected $request = null;
    /**
     * @var StoreManagerInterface
     * */
    protected $storeManager = null;
    /**
     * @var ScopeConfigInterface
     * */
    protected $_scopeConfig;

    public function __construct(
        NitroServiceInterface $nitro,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $_scopeConfig
    ) {
        $this->nitro = $nitro;
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->_scopeConfig = $_scopeConfig;
    }

    public function afterMatch(\Magento\Framework\App\RouterInterface $subject, $returnValue)
    {
        if (headers_sent() || NitroService::isANitroRequest()) {
            return $returnValue;
        }

        if (!$this->nitro->isConnected() || !$this->nitro->isEnabled()) {
            return $returnValue;
        }

        CacheTagObserver::disableObservers();

        $route = $this->request->getFullActionName();

        if($this->nitro->isCheckCartRoute($this->request->getPathInfo())){
            CacheTagObserver::enableObservers();
            return $returnValue;
        }
        if ($returnValue === null ||
            !$this->nitro->isCacheable() || // Magento specific checks if the request can be cached
            (!is_bool($returnValue) && get_class(
                    $returnValue
                ) == self::class) || // if a router just wraps around another one and calls its match method, we'll get an object with the same class name as a return value
            is_a(
                $returnValue,
                'Magento\Framework\App\Action\Forward'
            ) || // if a router returns an internal Forward action to have the request rerouted without an HTTP redirect
            is_a($returnValue, 'NitroPack\NitroPack\Controller\Webhook\CacheClear') || is_a(
                $returnValue,
                'NitroPack\NitroPack\Controller\Webhook\Config'
            ) || is_a($returnValue, 'NitroPack\NitroPack\Controller\Webhook\CacheReady')) { // NitroPack webhooks

            return $returnValue;
        }

        $store = $this->storeManager->getStore();
        $storeViewId = $store->getId();
        $storeId = $store->getStoreGroupId();
        $websiteId = $store->getWebsiteId();



        $layout = $websiteId . '_' . $storeId . '_' . $storeViewId . '_' . $route;

        if (defined('NITROPACK_DEBUG') && NITROPACK_DEBUG) {
            header('X-Nitro-Layout: ' . $layout);
        }
        if (!$this->nitro->isConnected() || !$this->nitro->isEnabled() || is_null(
                $this->_scopeConfig->getValue(\NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK)
            ) || $this->_scopeConfig->getValue(
                \NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK
            ) != \NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE) {
            header('X-Nitro-Disabled: 1');
            return $returnValue;
        }
        if($this->nitro->isSafeModeEnabled() && !$this->request->getParam('testnitro') ){
            header('X-Nitro-Disabled: 1');
            return $returnValue;
        }
        if ($this->request->getFrontName()=='checkout' ) {
            header('X-Nitro-Disabled: 1', true);
            return $returnValue;
        }
        try {
            if ($this->nitro->hasRemoteCache($layout)) {
                header('X-Nitro-Cache: HIT', true);
            }
        } catch (\Exception $exception) {
            header('X-Nitro-Disabled: 1', true);
            return $returnValue;
        }

        CacheTagObserver::enableObservers();

        return $returnValue;
    }

}
