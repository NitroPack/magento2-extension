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
namespace NitroPack\NitroPack\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\HTTP\Client\Curl;
use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Helper\NitroPackConfigHelper;
use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * Class EnableCaches - EnableCaches Controller to enable the NitroPack caches from dashboard page
 * @extends StoreAwareAction
 * @package NitroPack\NitroPack\Controller\Adminhtml\Settings
 * @since 2.0.0
 */
class EnableCaches extends StoreAwareAction
{
    /**
     * @var JsonFactory
     * */
    protected $resultJsonFactory;
    /**
     * @var NitroServiceInterface
     * */
    protected $nitro;
    /**
     * @var NitroPackConfigHelper
     * */
    protected $_helper;
    /**
     * @var TypeListInterface
     * */
    protected $cacheTypeList;
    /**
     * @var StateInterface
     * */
    protected $cacheState;
    protected static $cachesToEnable = 'full_page';

    const FULL_PAGE_CACHE_NITROPACK = 'system/full_page_cache/caching_application';

    /**
     * @var WriterInterface
     * */
    protected $configWriter;
    /**
     * @var ScopeConfigInterface
     * */
    protected $_scopeConfig;
    /**
     * @var \Magento\Framework\App\Cache\Frontend\Pool
     * */
    protected $cacheFrontendPool;

    /**
     * @param Context $context
     * @param NitroServiceInterface $nitro
     * @param TypeListInterface $cacheTypeList
     * @param NitroPackConfigHelper $_helper
     * @param ScopeConfigInterface $_scopeConfig
     * @param WriterInterface $configWriter
     * @param StateInterface $cacheState
     *
     * @param \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
     * */
    public function __construct(
        Context $context,
        NitroServiceInterface $nitro,
        TypeListInterface $cacheTypeList,
        NitroPackConfigHelper $_helper,
        ScopeConfigInterface $_scopeConfig,
        WriterInterface $configWriter,
        StateInterface $cacheState,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool

    ) {
        parent::__construct($context, $nitro);
        $this->resultJsonFactory = $this->_objectManager->create(JsonFactory::class);
        $this->nitro = $nitro;
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheState = $cacheState;
        $this->_scopeConfig = $_scopeConfig;
        $this->configWriter = $configWriter;
        $this->_helper = $_helper;
        $this->cacheFrontendPool = $cacheFrontendPool;
    }

    protected function nitroExecute()
    {

        try {
            $this->cacheTypeList->cleanType(self::$cachesToEnable);
            if (!$this->cacheState->isEnabled(self::$cachesToEnable)) {
                $this->cacheState->setEnabled(self::$cachesToEnable, true);
            }
            if(!$this->_helper->getFullPageCacheValue()){
                $this->configWriter->save(\NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK,\NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE,ScopeConfigInterface::SCOPE_TYPE_DEFAULT,  0);
                $baseUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
                if($this->_helper->isVarnishConfigured($baseUrl)){
                    $this->configWriter->save(\NitroPack\NitroPack\Api\NitroService::XML_VARNISH_PAGECACHE_NITRO_ENABLED,1,ScopeConfigInterface::SCOPE_TYPE_DEFAULT,  0);
                }
                $this->configWriter->save('full_page_cache/fields/caching_application/value', \NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE,ScopeConfigInterface::SCOPE_TYPE_DEFAULT,  0);
                $types = array_keys($this->cacheTypeList->getTypes());
                foreach ($types as $type) {
                    $this->cacheFrontendPool->get($type)->getBackend()->clean();
                }

            }
            $this->cacheState->persist();
            //Check the cache is enabled so Extension Should enabled
            $setting = $this->nitro->getSettings();
            if (isset($setting->previous_extension_status) && !$setting->previous_extension_status) {
                $extension_enabled = false;
            } else {
           $this->_helper->setBoolean('enabled', true);
                $this->nitro->persistSettings();
                $extension_enabled = true;
            }
            return $this->resultJsonFactory->create()->setData(array(
                'enabled' => true,
                'extension_enabled' => $extension_enabled
            ));
        } catch (\Exception $e) {
            return $this->resultJsonFactory->create()->setData(array(
                'enabled' => false,
                'extension_enabled' => false
            ));
        }
    }
}
