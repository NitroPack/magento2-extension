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

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;

/**
 * Class PostPageLoad  - Post Page Load Observer
 * @implements ObserverInterface
 * @package NitroPack\NitroPack\Observer
 * @since 2.0.0
 * */
class PostPageLoad implements ObserverInterface
{

    protected $nitro = null;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     * */
    protected $storeManager;
    /**
     * @var RequestInterface
     **/
    protected $request;
    /**
     * @var \NitroPack\NitroPack\Helper\NitroPackConfigHelper
     * */
    protected $nitroPackConfigHelper;

    public function __construct(
        NitroServiceInterface $nitro,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \NitroPack\NitroPack\Helper\NitroPackConfigHelper $nitroPackConfigHelper,
        RequestInterface $request
    ) {
        $this->request = $request;
        $this->nitroPackConfigHelper = $nitroPackConfigHelper;
        $this->storeManager = $storeManager;
        $this->nitro = $nitro;
    }

    public function execute(Observer $observer)
    {
        if (!$this->nitro->isConnected() || !$this->nitro->isEnabled() || !$this->nitroPackConfigHelper->getFullPageCacheValue(
            )) {
            return false;
        }

        CacheTagObserver::disableObservers();
        $store = $this->storeManager->getStore();
        $storeViewId = $store->getId();
        $storeId = $store->getStoreGroupId();
        $websiteId = $store->getWebsiteId();
        $route = $this->request->getFullActionName();
        $layout = $websiteId . '_' . $storeId . '_' . $storeViewId . '_' . $route;
        if (!$this->nitro->isCachableRoute($route)) {
            header('X-Nitro-Disabled: 1', true);
            return false;
        }
        if ($this->nitro->hasRemoteCache($layout)) {
            header('X-Nitro-Cache: HIT', true);
            return true;
        }
        CacheTagObserver::enableObservers();
    }
}
