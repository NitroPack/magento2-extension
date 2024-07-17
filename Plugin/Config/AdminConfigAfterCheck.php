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
namespace NitroPack\NitroPack\Plugin\Config;

use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\SDK\HealthStatus;

/**
 * Class AdminConfigAfterCheck - Admin Config After Check
 * @package NitroPack\NitroPack\Plugin\Config
 * @since 2.1.0
 * */
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
    /**
     * @var \Magento\Store\Api\GroupRepositoryInterface
     * */
    protected $storeRepo;
    /**
     * @param NitroServiceInterface $nitro
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Store\Api\GroupRepositoryInterface $storeRepo
     * */
    public function __construct(
        NitroServiceInterface                      $nitro,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Store\Api\GroupRepositoryInterface $storeRepo
    )
    {
        $this->nitro = $nitro;
        $this->storeManager = $storeManager;
        $this->storeRepo = $storeRepo;
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

        $storeGroup = $this->storeRepo->getList();
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
