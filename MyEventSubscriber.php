<?php

namespace NitroPack\NitroPack;

use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Plugin\PluginEvents;
use Composer\Script\Event;
use Composer\Plugin\PrePoolCreateEvent;


class MyEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'post-package-install' => 'onPostPackageInstall',
            PluginEvents::PRE_POOL_CREATE      => ['onPrePoolCreate', 1000],
        ];
    }

    public function onPrePoolCreate(PrePoolCreateEvent $event){

        die("HELLO");
    }
    public function onPostPackageInstall(Event $event)
    {
        $io = $event->getIO();
        $io->write('Running custom script after package install');
    }
}
