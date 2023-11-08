<?php

namespace NitroPack\NitroPack\Controller\Webhook;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Helper\VarnishHelper;
use NitroPack\SDK\PurgeType;
use Magento\Framework\App\RequestInterface;

class CacheClear extends WebhookController
{

    /**
     * @var RequestInterface
     * */
    protected $request;
    /**
     * @var ScopeConfigInterface
     * */
    private $_scopeConfig;
    /**
     * @var VarnishHelper
     * */
    protected $varnishHelper;

    /**
     * @param Context $context
     * @param RequestInterface $request
     * @param ScopeConfigInterface $_scopeConfig
     * @param VarnishHelper $varnishHelper
     * */
    public function __construct(Context $context, RequestInterface $request, ScopeConfigInterface $_scopeConfig, VarnishHelper $varnishHelper)
    {
        $this->_scopeConfig = $_scopeConfig;
        $this->request = $request;
        $this->varnishHelper = $varnishHelper;
        parent::__construct($context);
    }

    const REASON_MANUAL_PURGE_URL = "Manual purge of the link %s from the NitroPack.io Dashboard.";
    const REASON_MANUAL_PAGE_CACHE_ONLY_ALL = "Manual page cache clearing of all store pages from the NitroPack.io Dashboard.";

    public function execute()
    {
        if ($url = $this->getRequest()->getParam('url', false)) {
            if (!is_array($url)) {
                $this->purgeSingleUrl([$url]);

            } else {
                $this->purgeSingleUrl($url);
            }
            return $this->textResponse('ok');
        } else {
            $this->nitro->purgeLocalCache(true);
            if (
                !is_null(
                    $this->_scopeConfig->getValue(\NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK)
                )
                && $this->_scopeConfig->getValue(
                    \NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK
                ) == \NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE
                && isset($_SERVER['HTTP_X_VARNISH'])
            ) {
                $this->varnishHelper->purgeVarnish();
            }
        }
        return $this->textResponse('ok');
    }


    public function purgeSingleUrl($url)
    {
        foreach ($url as $urlValue) {
            $this->nitro->purgeCache(
                $urlValue,
                null,
                PurgeType::PAGECACHE_ONLY,
                sprintf(self::REASON_MANUAL_PURGE_URL, $urlValue)
            );
            if (
                !is_null(
                    $this->_scopeConfig->getValue(\NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK)
                )
                && $this->_scopeConfig->getValue(
                    \NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK
                ) == \NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE
                && isset($_SERVER['HTTP_X_VARNISH'])
            ) {
                $this->varnishHelper->purgeVarnish($urlValue);
            }
        }
    }
}
