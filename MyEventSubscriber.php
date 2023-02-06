<?php

namespace NitroPack\NitroPack;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginEvents;

use Composer\Plugin\PluginInterface;
use Composer\Plugin\PrePoolCreateEvent;


class MyEventSubscriber implements PluginInterface
{

    public function uninstall(Composer $composer, IOInterface $io)
    {
        die("HELO");
        // TODO: Implement uninstall() method.
    }
    public function activate(Composer $composer, IOInterface $io)
    {
        // TODO: Implement activate() method.
    }
    public function deactivate(Composer $composer, IOInterface $io)
    {
        // TODO: Implement deactivate() method.
    }


}
