<?php

namespace NitroPack\NitroPack\Block;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\UrlInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Store\Model\Store;
use NitroPack\NitroPack\Api\NitroServiceInterface;

class SettingsBlock extends Template
{


    protected static $instance = null;
    /**
     * @var NitroServiceInterface
     * */
    public $nitro;

    protected $storeGroup;
    /**
     * @var UrlInterface
     * */
    protected $_backendUrl;
    /**
     * @var StoreManagerInterface
     * */
    protected $_storeManager;
    /**
     * @var ScopeConfigInterface
     * */
    protected $_scopeConfig;
    /**
     * @var TypeListInterface
     * */
    protected $_cacheTypeList;
    /**
     * @var Store
     * */
    protected $store;


    /***
     * @param Context $context
     * @param NitroServiceInterface $nitro
     * @param UrlInterface $backendUrl
     * @param StoreManagerInterface $storeManager
     * @param RequestInterface $request
     * @param ScopeConfigInterface $scopeConfig
     * @param TypeListInterface $cacheTypeList
     * @param Store $store
     * @param array $data
     **/
    public function __construct(
        Context               $context, // required as part of the Magento\Backend\Block\Template constructor
        NitroServiceInterface $nitro, // dependency injection'ed
        UrlInterface          $backendUrl, // dependency injection'ed
        StoreManagerInterface $storeManager, // dependency injection'ed
        RequestInterface      $request, // dependency injection'ed
        ScopeConfigInterface  $scopeConfig, // dependency injection'ed
        TypeListInterface     $cacheTypeList, // dependency injection'ed
        Store                 $store, // dependency injection'ed
        array                 $data = [] // required as part of the Magento\Backend\Block\Template constructor
    )
    {
        parent::__construct($context, $data);
        $this->nitro = $nitro;
        $this->_backendUrl = $backendUrl;
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->_cacheTypeList = $cacheTypeList;
        $this->store = $store;
        self::$instance = $this;
    }

    public function getSettings()
    {
        $this->nitro->reload($this->_storeManager->getGroup($this->getRequest()->getParam('group'))->getCode());
        return $this->nitro->getSettings();
    }

    public static function getInstance()
    {
        return self::$instance;
    }

    protected function getBackendUrl($route, $withStore = true, $withFormKey = false)
    {
        $params = array();
        if ($withStore) {
            $params['group'] = $this->getStoreGroup()->getId();
        }
        if ($withFormKey) {
            $params['form_key'] = $this->getFormKey();
        }
        return $this->_backendUrl->getUrl($route, $params);
    }

    public function getSaveUrl()
    {
        return $this->getBackendUrl('NitroPack/settings/save');
    }

    public function getVarnishPurgeUrl()
    {
        return $this->getBackendUrl('NitroPack/purge');
    }

    public function getSafeModeEnableUrl()
    {
        return $this->getBackendUrl('NitroPack/settings/enablesafemode');
    }

    public function getCacheToCustomerLogin()
    {
        return $this->getBackendUrl('NitroPack/settings/enablecachetocustomerlogin');
    }

    public function checkVarnishEnable()
    {
        if (
            !is_null($this->_scopeConfig->getValue(\NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK))
            && $this->_scopeConfig->getValue(
                \NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK
            ) == \NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE
            && isset($_SERVER['HTTP_X_VARNISH'])
        ) {
            return 1;
        }
        return 0;
    }

    public function getIntegrationUrl($integration)
    {
        return $this->nitro->integrationUrl($integration);
    }

    public function getDisconnectUrl()
    {
        return $this->getBackendUrl('NitroPack/settings/disconnect', true, true);
    }

    public function getConnectUrl()
    {
        return $this->getBackendUrl('NitroPack/connect/index', true, false);
    }

    public function getStartWarmupUrl()
    {
        return $this->getBackendUrl('NitroPack/warmup/start', true, true);
    }

    public function getPauseWarmupUrl()
    {
        return $this->getBackendUrl('NitroPack/warmup/pause', true, true);
    }

    public function getEnableCachesUrl()
    {
        return $this->getBackendUrl('NitroPack/settings/enablecaches', true, true);
    }

    public function getCachePurgeUrl()
    {
        return $this->getBackendUrl('NitroPack/cache/purge', false, true);
    }


    public function getNumberOfPageStoreWise()
    {
        return $this->getBackendUrl('NitroPack/settings/NumberOfPageCountStoreWise', true, false);
    }
    public function getCacheManagementUrl()
    {
        // route the magento System > Tools > Cache management page
        return $this->getBackendUrl('adminhtml/cache/index', false, false);
    }

    public function getApplicationCacheUrl()
    {
        // route the magento System -> Configuration -> Advanced -> Full Page Cache
        return $this->getBackendUrl('adminhtml/system_config/edit/section/system', false, false);
    }

    public function getStoreGroup()
    {
        if (!$this->storeGroup) {
            $storeGroupId = (int)$this->_request->getParam('group', 0);
            if ($storeGroupId == 0) {
                $storeGroupId = $this->_storeManager->getGroup()->getId();
            }
            $store = $this->_storeManager->getGroup($storeGroupId);
            $this->storeGroup = $store;
        }
        return $this->storeGroup;
    }

    public function getStoreUrls()
    {
        $storeGroupId = (int)$this->_request->getParam('group', 0);
        if ($storeGroupId == 0) {
            $storeGroupId = $this->_storeManager->getGroup()->getId();
        }
        $group = $this->_storeManager->getGroup($storeGroupId);
        $url = '';

        $defaultStoreView = $this->_storeManager->getStore($group->getDefaultStoreId());
        $url = $this->store->isUseStoreInUrl() ? str_replace(
            $defaultStoreView->getCode() . '/',
            '',
            $defaultStoreView->getBaseUrl()
        ) : $defaultStoreView->getBaseUrl(); // get store view name


        return $url;
    }

    public function getAvailableCurrencies()
    {
        return $this->_storeManager->getStore()->getAvailableCurrencyCodes();
    }

    public function getAvailableLocales()
    {
        $locales = array();

        $currentStore = $this->_storeManager->getStore();
        $currentGroupId = $currentStore->getStoreGroupId();

        $stores = $this->_storeManager->getStores();
        foreach ($stores as $store) {
            if ($store->getStoreGroupId() != $currentGroupId) {
                continue;
            }
            $locales[] = $this->_scopeConfig->getValue(
                'general/locale/code',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store->getId()
            );
        }

        return $locales;
    }

    public function getBuiltInPageTypeRoutes()
    {
        return $this->nitro->getBuiltInPageTypeRoutes();
    }

    public function getWarmupStats()
    {
        $stats = $this->nitro->getApi()->getWarmupStats();
        $stats['is_warmup_active'] = (bool)$stats['status'] && (bool)$stats['pending'];
        return $stats;
    }


    public function getCacheLabels()
    {
        return $this->_cacheTypeList->getTypeLabels();
    }

}
