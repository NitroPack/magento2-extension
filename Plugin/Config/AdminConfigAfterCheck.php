<?php

namespace NitroPack\NitroPack\Plugin\Config;

use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\SDK\HealthStatus;


class AdminConfigAfterCheck
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     * */
    protected $storeManager;
    /**
     * @var NitroServiceInterface
     * */

    protected $nitro;

    public function __construct(
        NitroServiceInterface                      $nitro,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        $this->nitro = $nitro;
        $this->storeManager = $storeManager;
    }

    /**
     * After Save config section
     *
     * Require set: section, website, store and groups
     *
     * @return $this
     * @throws \Exception
     */
    public function afterSave()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeRepo = $objectManager->create(\Magento\Store\Api\GroupRepositoryInterface::class);
        $storeGroup = $storeRepo->getList();

        foreach ($storeGroup as $storeGroupData) {
            $storeGroupCode = $storeGroupData->getCode();

            $duplicateAliasDomain = [];
            foreach ($storeGroupData->getStores() as $storeView) {
                $defaultStoreView = $this->storeManager->getStore($storeGroupData->getDefaultStoreId());
                if ($storeGroupData->getDefaultStoreId() != $storeView->getId()) {
                    $parseStoreUrl = parse_url($storeView->getBaseUrl());
                    $parseDefaultStoreUrl = parse_url($defaultStoreView->getBaseUrl());
                    if ($parseStoreUrl['host'] != $parseDefaultStoreUrl['host']) {
                        if (strpos($parseStoreUrl['host'], '.' . $parseDefaultStoreUrl['host']) === false) {
                            $duplicateAliasDomain[] = $storeView->getBaseUrl();
                        }
                    }
                }
            }
            try {
                $this->nitro->reload($storeGroupCode);
                if ($this->nitro->isConnected() && $this->nitro->getSdk()->getHealthStatus() == HealthStatus::HEALTHY) {

                    $this->nitro->getSdk()->getApi()->disableAdditionalDomains();
                    //Add Alias Domain
                    if (count($duplicateAliasDomain) > 0) {
                        $this->nitro->getSdk()->getApi()->enableAdditionalDomains();
                        foreach ($duplicateAliasDomain as $duplicateAlias) {
                            $duplicateAliasUrl = parse_url($duplicateAlias);
                            $this->nitro->getSdk()->getApi()->removeAdditionalDomain($duplicateAliasUrl['host']);
                            $this->nitro->getSdk()->getApi()->addAdditionalDomain($duplicateAliasUrl['host']);
                        }
                    }
                }
            } catch (\Exception $e) {

            }

        }
        return $this;
    }
}
