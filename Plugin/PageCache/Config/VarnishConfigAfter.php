<?php

namespace NitroPack\NitroPack\Plugin\PageCache\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\PageCache\Model\Varnish\VclGeneratorFactory;
use Magento\PageCache\Model\Config;

class VarnishConfigAfter
{
    public const XML_PAGECACHE_TYPE = 'system/full_page_cache/caching_application';

    public const XML_VARNISH_PAGECACHE_ACCESS_LIST = 'system/full_page_cache/varnish_nitro/access_list';
    public const XML_VARNISH_PAGECACHE_NITRO_ENABLED = 'system/full_page_cache/varnish_enable';

    public const XML_VARNISH_PAGECACHE_BACKEND_PORT = 'system/full_page_cache/varnish_nitro/backend_port';

    public const XML_VARNISH_PAGECACHE_BACKEND_HOST = 'system/full_page_cache/varnish_nitro/backend_host';

    public const XML_VARNISH_PAGECACHE_GRACE_PERIOD = 'system/full_page_cache/varnish_nitro/grace_period';

    /**
     * @var ScopeConfigInterface
     **/
    protected $_scopeConfig;
    /**
     * @var VclGeneratorFactory
     **/
    protected $vclGeneratorFactory;
    /**
     * @var Json
     * */
    protected $serializer;

    public function __construct(
        VclGeneratorFactory $vclGeneratorFactory,
        ScopeConfigInterface $_scopeConfig,
        Json $serializer = null
    ) {
        $this->_scopeConfig = $_scopeConfig;
        $this->serializer = $serializer;
        $this->vclGeneratorFactory = $vclGeneratorFactory;
    }

    public function afterGetVclFile(\Magento\PageCache\Model\Config $subject, $vclTemplatePath, $returnValue)
    {



            $accessList = $this->_scopeConfig->getValue(self::XML_VARNISH_PAGECACHE_ACCESS_LIST);
            $designExceptions = $this->_scopeConfig->getValue(
                Config::XML_VARNISH_PAGECACHE_DESIGN_THEME_REGEX,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            switch ($vclTemplatePath) {
                case Config::VARNISH_6_CONFIGURATION_PATH:
                    $version = 6;
                    break;
                case Config::VARNISH_5_CONFIGURATION_PATH:
                    $version = 5;
                    break;
                default:
                    $version = 4;
            }
            $sslOffloadedHeader = $this->_scopeConfig->getValue(
                Request::XML_PATH_OFFLOADER_HEADER
            );

            $vclGenerator = $this->vclGeneratorFactory->create(
                [
                    'backendHost' => $this->_scopeConfig->getValue(self::XML_VARNISH_PAGECACHE_BACKEND_HOST),
                    'backendPort' => $this->_scopeConfig->getValue(self::XML_VARNISH_PAGECACHE_BACKEND_PORT),
                    'accessList' => $accessList ? explode(',', $accessList) : [],
                    'designExceptions' => $designExceptions ? $this->serializer->unserialize($designExceptions) : [],
                    'sslOffloadedHeader' => $sslOffloadedHeader,
                    'gracePeriod' => $this->_scopeConfig->getValue(self::XML_VARNISH_PAGECACHE_GRACE_PERIOD)
                ]
            );
            return $vclGenerator->generateVcl($version);

    }

}
