<?php

namespace NitroPack\NitroPack\Observer;

use Composer\Installer\PackageEvent;
use Composer\Script\Event;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class ComposerScript
{
    public static function composerUninstall(PackageEvent $event)
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
