<?php

namespace NitroPack\NitroPack\Controller\Webhook;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use NitroPack\NitroPack\Helper\VarnishHelper;
use NitroPack\NitroPack\Api\NitroService;

class Config extends WebhookController
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
     * @var \Magento\Framework\Filesystem\Driver\File
     * */
    protected $fileDriver;

    /**
     * @var WriterInterface
     * */
    protected $configWriter;

    /**
     * @param Context $context
     * @param VarnishHelper $varnishHelper
     * @param ScopeConfigInterface $_scopeConfig
     * @param \Magento\Framework\Filesystem\Driver\File $fileDriver
     * @param WriterInterface $configWriter
     * */
    public function __construct(Context $context, VarnishHelper $varnishHelper, ScopeConfigInterface $_scopeConfig, \Magento\Framework\Filesystem\Driver\File $fileDriver, WriterInterface $configWriter)
    {
        $this->_scopeConfig = $_scopeConfig;
        $this->varnishHelper = $varnishHelper;
        $this->fileDriver = $fileDriver;
        $this->configWriter = $configWriter;
        parent::__construct($context);
    }

    public function execute()
    {

        if ($this->nitro->fetchConfig()) {
            $settings = $this->nitro->getSettings();
            if (!is_null($settings)) {
                $nitroDataDir = str_replace('/pagecache', '', $this->nitro->getCacheDir());
                $configFileName = $nitroDataDir . '/' . $settings->siteId . '-config.json';
                if ($this->fileDriver->isExists($configFileName)) {
                    $info = json_decode(file_get_contents($configFileName), true);
                    if (isset($info['CacheIntegrations']) && isset($info['CacheIntegrations']['Varnish']) && isset($info['CacheIntegrations']['Varnish']['Servers']) && $info['CacheIntegrations']['Varnish']['Servers'] ) {
                        if ($this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_BACKEND_HOST) != implode(',', $info['CacheIntegrations']['Varnish']['Servers'])) {
                            $this->configWriter->save(NitroService::XML_VARNISH_PAGECACHE_BACKEND_HOST, implode(',', $info['CacheIntegrations']['Varnish']['Servers']), 'default', 0);
                        }
                    }
                }
            }

        }
        return $this->textResponse('ok');
    }
}
