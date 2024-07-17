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
namespace NitroPack\NitroPack\Observer;

use Composer\Installer\PackageEvent;
use Composer\Script\Event;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class ComposerScript
 * @implements ObserverInterface
 * @package NitroPack\NitroPack\Observer
 * @since 2.0.0
 * */
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
