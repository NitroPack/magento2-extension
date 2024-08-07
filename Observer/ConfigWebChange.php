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

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\Store;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\SDK\HealthStatus;

/**
 * Class ConfigWebChange - Store Variation Cookie Observer
 * @implements ObserverInterface
 * @package NitroPack\NitroPack\Observer
 * @since 2.3.3
 * */
class ConfigWebChange implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var ScopeConfigInterface
     * */
    private $_scopeConfig;
    /**
     * @var NitroServiceInterface
     * */
    private $nitro;

    /**
     * ConfigChange constructor.
     * @param RequestInterface $request
     * @param ScopeConfigInterface $scopeConfig
     * @param NitroServiceInterface $nitro
     */
    public function __construct(
        RequestInterface $request,
        ScopeConfigInterface $scopeConfig,
        NitroServiceInterface $nitro
    ) {
        $this->request = $request;
        $this->nitro = $nitro;
        $this->_scopeConfig = $scopeConfig;
    }

    public function execute(EventObserver $observer)
    {
        $groupParams = $this->request->getParam('groups');

        $storeCodeChange = isset($groupParams['url']['fields']['use_store']) &&  isset($groupParams['url']['fields']['use_store']['value']) ? $groupParams['url']['fields']['use_store']['value'] : false;
        //Check Add Variation Cookie for Store
        $storeViewCode = [];
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeRepo = $objectManager->create(\Magento\Store\Api\GroupRepositoryInterface::class);
        $storeGroup = $storeRepo->getList();

        foreach ($storeGroup as $storesData) {
            foreach ($storesData->getStores() as $store) {
                if ($storesData->getDefaultStoreId() != $store->getId()) {
                    $storeViewCode[] = $store->getCode(); // get store view name
                }
            }
            try {
                $this->nitro->reload($storesData->getCode());
                if (!is_null($this->nitro->getSdk())  && $this->nitro->getSdk()->getHealthStatus() == HealthStatus::HEALTHY) {
                    if (isset($storeCodeChange) && !$storeCodeChange) {
                        if (count($storeViewCode) > 0) {
                            $this->nitro->getSdk()->getApi()->setVariationCookie('store', $storeViewCode, 1);
                        } else {
                            $this->nitro->getSdk()->getApi()->unsetVariationCookie('store');
                        }
                    } else {
                        $this->nitro->getSdk()->getApi()->unsetVariationCookie('store');
                    }
                }
            } catch (\Exception $e) {
            }
        }
    }
}
