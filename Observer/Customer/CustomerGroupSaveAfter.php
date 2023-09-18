<?php

namespace NitroPack\NitroPack\Observer\Customer;

use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Helper\NitroPackConfigHelper;
use NitroPack\SDK\HealthStatus;

class CustomerGroupSaveAfter implements ObserverInterface
{
    /**
     * @var ScopeConfigInterface
     * */
    protected $_scopeConfig;

    /**
     * @param \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepo
     * */
    protected $storeGroupRepo;
    /**
     * @var NitroPackConfigHelper
     * */
    protected $nitroPackConfigHelper;
    /**
     * @var StateInterface
     * */
    protected $_cacheState;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     * */
    protected $serializer;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepo
     * @param NitroPackConfigHelper $nitroPackConfigHelper
     * @param NitroServiceInterface $nitro
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param StateInterface $_cacheState
     * */
    public function __construct(
        ScopeConfigInterface                             $scopeConfig,
        \Magento\Store\Api\GroupRepositoryInterface      $storeGroupRepo,
        NitroPackConfigHelper                            $nitroPackConfigHelper,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        StateInterface                                   $_cacheState
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->storeGroupRepo = $storeGroupRepo;
        $this->serializer = $serializer;
        $this->nitroPackConfigHelper = $nitroPackConfigHelper;

        $this->_cacheState = $_cacheState;
    }

    public function execute(Observer $observer)
    {
        $eventName = $observer->getEvent()->getName();
        $storeGroup = $this->storeGroupRepo->getList();

        if (!is_null(
                $this->_scopeConfig->getValue('system/full_page_cache/caching_application')
            ) && $this->_scopeConfig->getValue(
                'system/full_page_cache/caching_application'
            ) == NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE && $this->_cacheState->isEnabled('full_page')) {

            foreach ($storeGroup as $storesData) {
                    if ($eventName == 'controller_action_postdispatch_customer_group_delete') {
                        $this->nitroPackConfigHelper->xMagentoVaryDelete($storesData->getCode());
                    } else {

                        $this->nitroPackConfigHelper->xMagentoVaryAdd($storesData);
                    }

            }
        }
        return true;
    }
}
