<?php

namespace NitroPack\NitroPack\Model\FullPageCache;

use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Helper\FastlyHelper;
use NitroPack\NitroPack\Model\FullPageCache\FastlyCache;
use NitroPack\NitroPack\Model\FullPageCache\PurgeCache;


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
