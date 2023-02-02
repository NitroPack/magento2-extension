<?php

namespace NitroPack\NitroPack\Observer;

use Composer\Installer\PackageEvent;
use Composer\Script\Event;

class ComposerScript
{
    public static function composerUninstall(PackageEvent $event)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/composer.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("HELLO");

    }



    public static function preInstall(Event $event) {
        // provides access to the current ComposerIOConsoleIO
        // stream for terminal input/output
        $io = $event->getIO();
        if ($io->askConfirmation("Are you sure you want to proceed? ", false)) {
            // ok, continue on to composer install
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/composer.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info("INSTALL");
            return true;
        }
        // exit composer and terminate installation process
        exit;
    }
}
