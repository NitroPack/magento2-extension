<?php

namespace NitroPack\NitroPack\Plugin\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroService;
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
    /**
     * @var RequestInterface
     * */
    protected $request;
    /**
     * @param NitroServiceInterface $nitro
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $_scopeConfig
     * @param RequestInterface $request
     * */
    public function __construct(
        NitroServiceInterface                      $nitro,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ScopeConfigInterface                       $_scopeConfig,
        RequestInterface                           $request
    )
    {
        $this->_scopeConfig = $_scopeConfig;
        $this->nitro = $nitro;
        $this->storeManager = $storeManager;
        $this->request = $request;
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
                    $this->varnishConfiguredSetup();
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

    public function varnishConfiguredSetup()
    {
        try {

                if (
                    !is_null($this->_scopeConfig->getValue(NitroService::FULL_PAGE_CACHE_NITROPACK))
                    && $this->_scopeConfig->getValue(
                        NitroService::FULL_PAGE_CACHE_NITROPACK
                    ) == NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE
                    && !is_null($this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_BACKEND_HOST))
                    && !is_null($this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_NITRO_ENABLED))
                    && $this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_NITRO_ENABLED)
                ) {
                    if (!is_null($this->nitro->getSdk())) {
                    // Config url check because the value is reset via configuration
                    $backendServer = explode(
                        ',',
                        $this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_BACKEND_HOST)
                    );
                    $backendServer = array_map(function ($backendValue) {
                        $backendHostAndPort = explode(":", $backendValue);
                        if ($backendHostAndPort[0] == "localhost" || $backendHostAndPort[0] == '127.0.0.1') {
                            if (isset($backendHostAndPort[1]) && $backendHostAndPort[1] == 80) {
                                return "127.0.0.1";
                            }
                            if (isset($backendHostAndPort[1])) {
                                return "127.0.0.1:" . $backendHostAndPort[1];
                            }
                        }
                        return $backendValue;
                    }, $backendServer);

                    $varnish = $this->nitro->initializeVarnish();
                    $url = $this->request->isSecure() ? 'https://' . $this->request->getHttpHost() : 'http://' . $this->request->getHttpHost();
                    try {
                        $varnish->configure([
                            'Servers' => $backendServer,
                            'PurgeAllUrl' => $url,
                            'PurgeAllMethod' => 'PURGE',
                            'PurgeSingleMethod' => 'PURGE',
                        ]);
                        $varnish->enable();
                        $this->nitro->getSdk()->setVarnishProxyCacheHeaders([
                            'X-Magento-Tags-Pattern' => ' .*'
                        ]);
                    } catch (\Exception $e) {
                        return false;
                    }

                }

            }
        } catch (\Exception $exception) {
            return false;
        }

    }
}
