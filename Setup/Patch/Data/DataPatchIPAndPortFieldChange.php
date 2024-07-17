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
namespace NitroPack\NitroPack\Setup\Patch\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use NitroPack\NitroPack\Api\NitroService;


class DataPatchIPAndPortFieldChange implements DataPatchInterface
{
    /**
     * @var ScopeConfigInterface
     **/
    protected $_scopeConfig;

    /**
     * @var WriterInterface
     * */
    protected $configWriter;

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ScopeConfigInterface     $_scopeConfig,
        WriterInterface          $configWriter

    )
    {

        /**
         * If before, we pass $setup as argument in install/upgrade function, from now we start
         * inject it with DI. If you want to use setup, you can inject it, with the same way as here
         */
        $this->configWriter = $configWriter;
        $this->_scopeConfig = $_scopeConfig;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        if (!is_null($this->_scopeConfig->getValue('system/nitro_varnish/backend_host')) && !is_null($this->_scopeConfig->getValue('system/nitro_varnish/varnish_port'))) {
            $dataValue = $this->_scopeConfig->getValue('system/nitro_varnish/backend_host');
            $dataValuePort = $this->_scopeConfig->getValue('system/nitro_varnish/varnish_port');
            if ($this->_scopeConfig->getValue('system/nitro_varnish/backend_host') == 'localhost') {
                if ($dataValuePort == '80') {
                    $dataValue = '127.0.0.1';
                } else {
                    $dataValue = '127.0.0.1:' . $dataValuePort;
                }
            }else{
                $dataValue = $dataValue . ':' . $dataValuePort;
            }
            $this->configWriter->save(NitroService::XML_VARNISH_PAGECACHE_BACKEND_HOST, $dataValue, 'default', 0);
        }
        $this->moduleDataSetup->getConnection()->endSetup();
    }


    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }


    public function getWebhookUrls($token)
    {
        $urls = array(
            'config' => new NitropackUrl($this->urlHelper->getUrl('NitroPack/Webhook/Config') . '?token=' . $token),
            'cache_clear' => new NitropackUrl($this->urlHelper->getUrl('NitroPack/Webhook/CacheClear') . '?token=' . $token),
            'cache_ready' => new NitropackUrl($this->urlHelper->getUrl('NitroPack/Webhook/CacheReady') . '?token=' . $token)
        );

        return $urls;
    }


    public function nitroGenerateWebhookToken($siteId)
    {

        return $this->encryptor->hash($this->directoryList->getPath('var') . ":" . $siteId);
    }
}
