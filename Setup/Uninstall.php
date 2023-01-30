<?php

namespace NitroPack\NitroPack\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use Magento\Framework\Module\StatusFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

use NitroPack\NitroPack\Helper\VarnishHelper;


class Uninstall implements UninstallInterface
{
    /**
     * @var \NitroPack\NitroPack\Model\NitroPackEvent\Trigger
     * */
    protected $trigger;
    /**
     * @var WriterInterface
     * */
    protected $configWriter;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     * */
    protected $scopeConfig;
    /**
     * @var VarnishHelper
     * */
    protected $varnishHelper;

    public function __construct(
        \NitroPack\NitroPack\Model\NitroPackEvent\Trigger $trigger,
        WriterInterface $configWriter,
        VarnishHelper $varnishHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->trigger = $trigger;
        $this->configWriter = $configWriter;
        $this->varnishHelper = $varnishHelper;
        $this->scopeConfig = $scopeConfig;
    }

    public function uninstall(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();
        //TRIGGER AN EVENT OF UNINSTALL
        $this->trigger->hitEvent('uninstall', false);
        if ($this->scopeConfig->getValue('system/full_page_cache/varnish_enable')) {
            $this->setData('system/full_page_cache/caching_application', \Magento\PageCache\Model\Config::VARNISH);
        } else {
            $this->setData('system/full_page_cache/caching_application', \Magento\PageCache\Model\Config::BUILT_IN);
        }
        $this->varnishHelper->purgeVarnish();
        $setup->endSetup();
    }


    public function setData($path, $value)
    {
        $this->configWriter->save($path, $value, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
    }

    public static function composerUninstall()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $trigger = $objectManager->create(\NitroPack\NitroPack\Model\NitroPackEvent\Trigger::class);
        $scopeConfig = $objectManager->create(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $configWriter = $objectManager->create(WriterInterface::class);
        $trigger->hitEvent('uninstall', false);

        if ($scopeConfig->getValue('system/full_page_cache/varnish_enable')) {
            $configWriter->save(
                'system/full_page_cache/caching_application',
                \Magento\PageCache\Model\Config::VARNISH,
                $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $scopeId = 0
            );
        } else {
            $configWriter->save(
                'system/full_page_cache/caching_application',
                \Magento\PageCache\Model\Config::BUILT_IN,
                $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $scopeId = 0
            );
        }
        $helper = $objectManager->create(\NitroPack\NitroPack\Model\NitroPackEvent\Trigger::class);
        $helper->purgeVarnish();
    }

}
