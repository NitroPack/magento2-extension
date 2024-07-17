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
namespace NitroPack\NitroPack\Model\FullPageCache;

use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Helper\FastlyHelper;


/**
 * Class IntegratedCache - Integrated Cache
 * @package NitroPack\NitroPack\Model\FullPageCache
 * @since 2.9.0
 * */
class IntegratedCache implements \NitroPack\NitroPack\Model\FullPageCache\IntegratedCacheInterface
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     **/
    protected $_scopeConfig;

    /**
     * @var \NitroPack\NitroPack\Helper\FastlyHelper
     **/
     protected $fastlyHelper;
    protected $integratedCache;
    public const XML_FASTLY_PAGECACHE_NITRO_ENABLED = 'system/full_page_cache/enable_nitropack';
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        FastlyHelper  $fastlyHelper


    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->fastlyHelper = $fastlyHelper;

    }

    public function getIntegratedCache()
    {
        return  $this->integratedCache ;
    }

    public function setIntegratedCache()
    {
        if (
            !is_null($this->_scopeConfig->getValue(NitroService::FULL_PAGE_CACHE_NITROPACK))
            && $this->_scopeConfig->getValue(
                NitroService::FULL_PAGE_CACHE_NITROPACK
            ) == NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE
            && !is_null($this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_BACKEND_HOST))
            && !is_null($this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_NITRO_ENABLED))
            && $this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_NITRO_ENABLED)
        ) {
            $this->integratedCache = 'varnish';
        }
        if (
            $this->fastlyHelper->isFastlyAndNitroPackEnabled()
        ) {
            $this->integratedCache = 'fastly';
        }
        return null;
    }


}
