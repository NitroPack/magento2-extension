<?php

namespace NitroPack\NitroPack;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\ObjectManagerInterface;

class ComposerUninstall implements PluginInterface
{

    public function uninstall(Composer $composer, IOInterface $io)
    {

    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        // TODO: Implement deactivate() method.
    }

    public function activate(Composer $composer, IOInterface $io)
    {
//        $installer = new \NitroPack\NitroPack\NitroPackInstaller($io, $composer, 'library',null,null,\Magento\Framework\ObjectManagerInterface::class);
//        $composer->getInstallationManager()->addInstaller($installer);
    }


}
