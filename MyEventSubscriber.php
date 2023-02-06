<?php

namespace NitroPack\NitroPack;

use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Plugin\PluginEvents;

use Composer\Plugin\PrePoolCreateEvent;


class MyEventSubscriber implements EventSubscriberInterface
{

    protected $composer;
    protected $io;

    public static function getSubscribedEvents()
    {
        return [
            'pre-package-uninstall'=> ['onPrePoolCreate', 1000]

        ];
    }

    public function onPrePoolCreate(PrePoolCreateEvent $event)
    {
        die("HELLO");
    }


}
