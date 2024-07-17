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
namespace NitroPack\NitroPack\Helper;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use NitroPack\NitroPack\Api\NitroService;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class VarnishHelper - Varnish purging and use nitropack varnish initial helper for NitroPack
 * @extends AbstractHelper
 * @package NitroPack\NitroPack\Helper
 * @since 2.0.0
 */
class VarnishHelper extends AbstractHelper
{

    private const XML_PATH_ADDITIONAL_VARNISH_HEADERS = 'nitropack/varnish/varnish_additional_headers';
    /**
     * @var ScopeConfigInterface
     * */
    protected $_scopeConfig;

    /**
     * @var RequestInterface
     * */
    protected $request;

    /**
     * @var Json
     */
    protected $serializer;


    /**
     * @param Context $context
     * @param RequestInterface $request
     * @param ScopeConfigInterface $_scopeConfig
     * @param Json $serializer
     */
    public function __construct(
        Context              $context,
        RequestInterface     $request,
        ScopeConfigInterface $_scopeConfig,
        Json                 $serializer
    )
    {
        parent::__construct($context);

        $this->request = $request;
        $this->_scopeConfig = $_scopeConfig;
        $this->serializer = $serializer;
    }

    public function purgeVarnish($url = null)
    {
        $urlValue = !is_null($url) ? parse_url($url) : ['path'=>'/'];
        if(!is_null($this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_BACKEND_HOST))) {
            $backendServer = explode(
                ',',
                $this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_BACKEND_HOST)
            );

            $backendServers = array_map(function ($backendValue) {
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

            foreach ($backendServers as $backendServer) {
                $scheme = 'http://';
                if (isset($backendServer) && !empty($backendServer)) {
                    $url = $scheme . $backendServer;
                    $url = isset($urlValue['path']) ? $url . $urlValue['path'] : '';
                    $reverseProxy = new \NitroPack\SDK\Integrations\ReverseProxy(
                        [$backendServer],
                        'PURGE',
                        $this->getHeaders('.*', $backendServer)
                    );
                    try {
                        $reverseProxy->purge($url);
                    } catch (\Exception $e) {
                        throw new \RuntimeException($e->getMessage());
                    }
                }
            }
            return '';
        }
    }

    /**
     * @return string[]
     */
    private function getHeaders($pattern, $backendServer)
    {
        $result = ['X-Magento-Tags-Pattern' => $pattern];

        $additionalHeaders = $this->_scopeConfig->getValue(self::XML_PATH_ADDITIONAL_VARNISH_HEADERS);

        if ($additionalHeaders) {
            try {
                $headers = $this->serializer->unserialize($additionalHeaders);
            } catch (Exception $exception) {
                return $result;
            }

            if (is_array($headers)) {
                foreach ($headers as $header) {
                    $reverseProxy = $header['reverse_proxy'] ?? null;
                    $headerName = $header['name'] ?? null;
                    $headerValue = $header['value'] ?? null;
                    if ($headerName && $headerValue && $reverseProxy)
                        if ($reverseProxy == $backendServer) {
                        $result[$headerName] = $headerValue;
                    }
                }
            }
        }
        return $result;
    }
}
