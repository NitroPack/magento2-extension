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
namespace NitroPack\NitroPack\Controller\Remote;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Model\FullPageCache\PurgeInterface;
/**
 * Class Index - Controller Index to remote cache
 * @extends \Magento\Framework\App\Action\Action
 * @implements HttpPostActionInterface
 * @package NitroPack\NitroPack\Controller\Remote
 * @since 2.0.0
 */
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
     * @var PurgeInterface
     * */
    protected $purgeInterface;
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

    /**
     * @param Context $context
     * @param NitroServiceInterface $nitro
     * @param PurgeInterface $purgeInterface
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepository
     * @param RequestInterface $request
     * */
    public function __construct(
        Context                                     $context,
        NitroServiceInterface                       $nitro,
        PurgeInterface                               $purgeInterface,
        ScopeConfigInterface                        $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface  $storeManager,
        \Magento\Store\Api\StoreRepositoryInterface $storeRepository,
        RequestInterface                            $request
    )
    {
        $this->nitro = $nitro;
        $this->request = $request;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->storeRepository = $storeRepository;
        $this->purgeInterface = $purgeInterface;
        parent::__construct($context);
    }

    function execute()
    {
        $route = $this->request->getParam('route');
        $url = $this->request->getParam('currentUrl');
        $storeCode = $this->request->getParam('storeCode');
        $store = $this->storeRepository->get($storeCode);
        $storeGroupCode = $this->_storeManager->getGroup($store->getStoreGroupId())->getCode();
        $this->nitro->reload($storeGroupCode, $url);
        $setting = $this->nitro->getSettings();
        if ($setting->siteId && $setting->siteSecret) {
            if ($this->request->getFrontName()=='checkout') {
                header('X-Nitro-Disabled: 1', true);
                return false;
            }
            $nitroHeaderMiss = false;
            foreach (headers_list() as $headers) {
                $values = explode(":", $headers);
                if (trim(strtolower($values[0])) == 'x-nitro-cache' && trim($values[1]) == 'MISS') {
                    $nitroHeaderMiss = true;
                }
            }

            if ($nitroHeaderMiss && ($this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_NITRO_ENABLED) || $this->_scopeConfig->getValue(NitroService::XML_FASTLY_PAGECACHE_ENABLE_NITRO)  )&& $this->nitro->getSdk()->isAllowedUrl($this->request->getParam('currentUrl'))) {
                $this->purgeInterface->purge($this->request->getParam('currentUrl'));
            }



            if ($this->nitro->getSdk()->hasRemoteCache($route)) {
                header('X-Nitro-Cache: HIT', true);
                return true;
            }
        }
        return false;
    }

}
