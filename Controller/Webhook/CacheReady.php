<?php

namespace NitroPack\NitroPack\Controller\Webhook;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Helper\VarnishHelper;

class CacheReady extends WebhookController
{
    /**
     * @var  VarnishHelper
     * */
    protected $varnishHelper;
    /**
     * @var ScopeConfigInterface
     **/
    protected $_scopeConfig;

    /**
     * @param Context $context
     * @param VarnishHelper $varnishHelper
     * @param ScopeConfigInterface $_scopeConfig
     * */
    public function __construct(Context $context, VarnishHelper $varnishHelper, ScopeConfigInterface $_scopeConfig)
    {
        $this->_scopeConfig = $_scopeConfig;
        $this->varnishHelper = $varnishHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        if ($url = $this->getRequest()->getParam('url', false)) {
            $this->nitro->reload(null, $url);
            $this->nitro->hasRemoteCache('', false);
            if (
                !is_null($this->_scopeConfig->getValue(NitroService::FULL_PAGE_CACHE_NITROPACK))
                && $this->_scopeConfig->getValue(
                    NitroService::FULL_PAGE_CACHE_NITROPACK
                ) == NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE
                && !is_null($this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_BACKEND_HOST))
                && !is_null($this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_NITRO_ENABLED))
                && $this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_NITRO_ENABLED)
            ) {
                $this->varnishHelper->purgeVarnish($url);
            }
        }
        return $this->textResponse('ok');
    }

}
