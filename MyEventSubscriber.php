<?php

namespace NitroPack\NitroPack;

use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Script\Event;

class MyEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'post-package-install' => 'onPostPackageInstall',
        ];
    }

    public function onPostPackageInstall(Event $event)
    {
        $io = $event->getIO();
        $io->write('Running custom script after package install');
    }
}
