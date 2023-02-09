<?php

namespace NitroPack\NitroPack\Controller\Remote;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Helper\VarnishHelper;

class Index extends Action implements HttpPostActionInterface
{
    /**
     * @var NitroServiceInterface
     * */
    protected $nitro;
    /**
     * @var RequestInterface
     * */
    protected $request;
    /**
     * @var VarnishHelper
     * */
    protected $varnishHelper;
    /**
     * @var ScopeConfigInterface
     * */
    protected $_scopeConfig;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     * */
    protected $_storeManager;
    /**
     * @var \Magento\Store\Api\StoreRepositoryInterface
     * */
    protected $storeRepository;

    public function __construct(
        Context $context,
        NitroServiceInterface $nitro,
        VarnishHelper $varnishHelper,
        ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Store\Api\StoreRepositoryInterface $storeRepository,

        RequestInterface $request
    ) {
        $this->nitro = $nitro;
        $this->request = $request;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->storeRepository = $storeRepository;
        $this->varnishHelper = $varnishHelper;
        parent::__construct($context);
    }

    function execute()
    {
        $route = $this->request->getParam('route');
        $url = $this->request->getParam('currentUrl');
        $storeCode = $this->request->getParam('storeCode');
        $store = $this->storeRepository->get($storeCode);

        $storeGroupCode = $this->_storeManager->getGroup($store->getStoreGroupId())->getCode();
        $siteId = $this->request->getParam('siteId');
        $siteSecret = $this->request->getParam('siteSecret');
        $this->nitro->reload($storeGroupCode, $url);
        $setting = $this->nitro->getSettings();
        if ($setting->siteId == $siteId && $setting->siteSecret == $siteSecret) {
            if (!$this->nitro->isCachableRoute($route)) {
                header('X-Nitro-Disabled: 1', true);
                return false;
            }
            $nitroHeaderMiss = false;
            $magentoHeaderMiss = false;
            foreach (headers_list() as $headers) {
                $values = explode(":", $headers);
                if (trim(strtolower($values[0])) == 'x-nitro-cache' && trim($values[1]) == 'MISS') {
                    $nitroHeaderMiss = true;
                }
                if (trim(strtolower($values[0])) == 'x-magento-cache-debug' && trim($values[1]) == 'MISS') {
                    $magentoHeaderMiss = true;
                }
            }
            if (!$magentoHeaderMiss && $nitroHeaderMiss) {
                $this->varnishHelper->purgeVarnish($this->request->getParam('currentUrl'));
            }

            if ($this->nitro->getSdk()->hasRemoteCache($route)) {
                header('X-Nitro-Cache: HIT', true);
                return true;
            }
        }
        return false;
    }


}
