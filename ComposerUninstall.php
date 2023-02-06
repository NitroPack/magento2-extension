<?php

namespace NitroPack\NitroPack;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class ComposerUninstall implements PluginInterface
{

    public function uninstall(Composer $composer, IOInterface $io)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $scopeConfig = $objectManager->create(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        //TRIGGER AN EVENT OF UNINSTALL
        //$trigger->hitEvent('uninstall', false);
        if ($scopeConfig->getValue('system/full_page_cache/varnish_enable')) {
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
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $configWriter = $objectManager->create(WriterInterface::class);
        $configWriter->save($path, $value, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
    }
}
