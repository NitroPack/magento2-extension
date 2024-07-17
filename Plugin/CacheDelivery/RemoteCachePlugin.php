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
namespace NitroPack\NitroPack\Plugin\CacheDelivery;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Helper\FastlyHelper;
use NitroPack\NitroPack\Observer\CacheTagObserver;

/**
 * Class RemoteCachePlugin - Main Plugin Check Remote Cache
 * @package NitroPack\NitroPack\Plugin\CacheDelivery
 * @since 2.0.0
 * */
class RemoteCachePlugin
{
    private const NOROUTE_ACTION_NAME = 'cms_noroute_index';

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
    /**
     * @var FastlyHelper
     * */
    protected $fastlyHelper;
    /**
     * @param  NitroServiceInterface $nitro
     * @param  RequestInterface $request
     * @param  FastlyHelper $fastlyHelper
     * @param  StoreManagerInterface $storeManager
     * @param  ScopeConfigInterface $_scopeConfig
     * */
    public function __construct(
        NitroServiceInterface $nitro,
        RequestInterface $request,
        FastlyHelper $fastlyHelper,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $_scopeConfig
    ) {
        $this->nitro = $nitro;
        $this->request = $request;
        $this->fastlyHelper = $fastlyHelper;
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

        if($this->nitro->isCheckCartOrCustomerRoute($this->request->getPathInfo())){
            CacheTagObserver::enableObservers();
            return $returnValue;
        }
        if ($returnValue === null ||
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

        if ($route == self::NOROUTE_ACTION_NAME) {
            header('X-Nitro-Disabled: 1', true);
            header('X-Nitro-Disabled-Reason: 404', true);
            return $returnValue;
        }

        if (!$this->nitro->isConnected() || !$this->nitro->isEnabled() ||
            is_null(
                $this->_scopeConfig->getValue(\NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK)
            ) || !in_array($this->_scopeConfig->getValue(
                \NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK
            ),[\NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE,NitroService::FASTLY_CACHING_APPLICATION_VALUE])){
            header('X-Nitro-Disabled: 1');
            return $returnValue;
        }
        //Check NitroPack With Fastly Disable
        if($this->fastlyHelper->isFastlyAndNitroDisable()){
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
        if ($this->nitro->isCustomerLogin() && !$this->nitro->isCustomerLoginEnable() ) { // Magento specific checks if the request can be cached

            header('X-Nitro-Disabled: 1', true);
            CacheTagObserver::enableObservers();
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
