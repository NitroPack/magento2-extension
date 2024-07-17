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
namespace NitroPack\NitroPack\Controller\Webhook;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use NitroPack\NitroPack\Api\NitroService;

/**
 * Class Config - Controller Config for NitroPack Webhook
 * @extends \Magento\Framework\App\Action\Action
 * @package NitroPack\NitroPack\Controller\Webhook
 * @since 2.0.0
 */
class Config extends WebhookController
{

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
     * @param ScopeConfigInterface $_scopeConfig
     * @param \Magento\Framework\Filesystem\Driver\File $fileDriver
     * @param WriterInterface $configWriter
     * */
    public function __construct(Context $context, ScopeConfigInterface $_scopeConfig, \Magento\Framework\Filesystem\Driver\File $fileDriver, WriterInterface $configWriter)
    {
        $this->_scopeConfig = $_scopeConfig;
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
