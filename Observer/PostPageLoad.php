<?php

namespace NitroPack\NitroPack\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;

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
