<?php

namespace NitroPack\NitroPack\Helper;

use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Filesystem\DirectoryList;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Api\NitroServiceInterface;

class VarnishHelper extends AbstractHelper
{
    /**
     * @var ScopeConfigInterface
     * */
    protected $_scopeConfig;

    /**
     * @var RequestInterface
     * */
    protected $request;
    /**
     * @param Context $context
     * @param RequestInterface $request
     * @param ScopeConfigInterface $_scopeConfig
     * */
    public function __construct(
        Context $context,
        RequestInterface $request,
        ScopeConfigInterface $_scopeConfig
    ) {
        parent::__construct($context);

        $this->request = $request;
        $this->_scopeConfig = $_scopeConfig;
    }

    public function purgeVarnish($url = null)
    {
        $urlValue = !is_null($url) ? parse_url($url) : ['path'=>'/'];
        if(!is_null($this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_BACKEND_HOST))) {
            $backendServer = explode(
                ',',
                $this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_BACKEND_HOST)
            );
            $backendServer = array_map(function ($backendValue) {
                if ($backendValue == "localhost") {
                    return "127.0.0.1";
                }
                return $backendValue;
            }, $backendServer);
            $url = 'http://' . isset($backendServer[0]) ? $backendServer[0] : '';
            $url = !empty($url) ? isset($urlValue['path']) ? 'http://' . $url . $urlValue['path'] : '' : '';

            $reverseProxy = new \NitroPack\SDK\Integrations\ReverseProxy(
                $backendServer,
                'PURGE',
                ['X-Magento-Tags-Pattern' => '.*']
            );
            try {
                $reverseProxy->purge($url);
            } catch (\Exception $e) {
                throw new \RuntimeException($e->getMessage());
            }
            return '';
        }
    }
}
