<?php

namespace NitroPack\NitroPack;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use NitroPack\NitroPack\Helper\VarnishHelper;

class ComposerUninstall implements PluginInterface
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
     * @var \NitroPack\NitroPack\Helper\VarnishHelper
     * */
    protected $varnishHelper;

    public function __construct(
        \NitroPack\NitroPack\Model\NitroPackEvent\Trigger $trigger,
        WriterInterface $configWriter,
        \NitroPack\NitroPack\Helper\VarnishHelper $varnishHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->trigger = $trigger;
        $this->configWriter = $configWriter;
        $this->varnishHelper = $varnishHelper;
        $this->scopeConfig = $scopeConfig;
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        //TRIGGER AN EVENT OF UNINSTALL
        $this->trigger->hitEvent('uninstall', false);
        if ($this->scopeConfig->getValue('system/full_page_cache/varnish_enable')) {
            $this->setData('system/full_page_cache/caching_application', \Magento\PageCache\Model\Config::VARNISH);
        } else {
            $this->setData('system/full_page_cache/caching_application', \Magento\PageCache\Model\Config::BUILT_IN);
        }
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        // TODO: Implement deactivate() method.
    }

    public function activate(Composer $composer, IOInterface $io)
    {
        // TODO: Implement activate() method.
    }

    private function setData($path, $value)
    {
        $this->configWriter->save($path, $value, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
    }
}
