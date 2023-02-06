<?php

namespace NitroPack\NitroPack;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Plugin\PrePoolCreateEvent;


class MyEventSubscriber implements EventSubscriberInterface, PluginInterface
{

    protected $composer;
    protected $io;

    public static function getSubscribedEvents()
    {
        return [
            'post-package-install' => ['onPostPackageInstall', 1000],
            PluginEvents::PRE_POOL_CREATE => ['onPrePoolCreate', 1000],
        ];
    }

    public function onPrePoolCreate(PrePoolCreateEvent $event)
    {
        die("HELLO");
    }

    public function onPostPackageInstall(Event $event)
    {
        $io = $event->getIO();
        $io->write('Running custom script after package install');
    }


    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $installer = new TemplateInstaller($io, $composer);
        $composer->getInstallationManager()->addInstaller($installer);
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        // TODO: Implement uninstall() method.
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        // TODO: Implement deactivate() method.
    }
}
