<?php

namespace NitroPack\NitroPack\Observer\FullPageCache;

use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\SDK\NitroPack;

class Clear implements ObserverInterface
{
    /**
     * @var NitroServiceInterface
     * */
    private $nitro;

    private $stores = null;
    /**
     * @var ScopeConfigInterface
     * */
    protected $_scopeConfig;
    /**
     * @var StateInterface
     * */
    protected $_cacheState;

    public function __construct(
        NitroServiceInterface $nitro,
        \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepository,
        ScopeConfigInterface $_scopeConfig,
        StateInterface $_cacheState
    ) {
        $this->nitro = $nitro;
        $this->_scopeConfig = $_scopeConfig;
        $this->_cacheState = $_cacheState;
        /**
         * @var \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepository
         */
        $this->stores = $storeGroupRepository->getList();
    }

    public function execute(Observer $observer)
    {
        if (!is_null(
                $this->_scopeConfig->getValue('system/full_page_cache/caching_application')
            ) && $this->_scopeConfig->getValue(
                'system/full_page_cache/caching_application'
            ) == NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE && $this->_cacheState->isEnabled('full_page')) {
            foreach ($this->stores as $storeId => $storeData) {
                if ($storeData->getCode() != 'admin') {
                    $this->nitro->reload($storeData->getCode());
                    if ($this->nitro->isConnected() && $this->nitro->isEnabled()) {
                        $this->nitro->purgeCache(
                            null,
                            null,
                            \NitroPack\SDK\PurgeType::COMPLETE,
                            "Magento cache flush remove all page cache"
                        );
                    }
                }
            }
        }
    }
}
