<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Bridge\Symfony\Event;

use Blackfire\Bridge\Symfony\MonitorableCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This subscriber automatically sends traces to Blackfire Monitoring for
 * eligible commands. Transactions are named after the command name.
 *
 * To be eligible, commands need to implement MonitorableCommandInterface.
 */
class MonitoredCommandSubscriber implements EventSubscriberInterface
{
    private $enabled;

    public function __construct()
    {
        $this->enabled = extension_loaded('blackfire') && method_exists(\BlackfireProbe::class, 'startTransaction');
    }

    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        if (!$this->enabled) {
            return;
        }

        $command = $event->getCommand();
        if (!$this->isTracingEnabled($command)) {
            return;
        }

        \BlackfireProbe::setTransactionName($command->getName());
        \BlackfireProbe::startTransaction();
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        if (!$this->enabled) {
            return;
        }

        if (!$this->isTracingEnabled($event->getCommand())) {
            return;
        }

        \BlackfireProbe::stopTransaction();
    }

    private function isTracingEnabled(Command $command)
    {
        return $command instanceof MonitorableCommandInterface;
    }

    public static function getSubscribedEvents()
    {
        return array(
            ConsoleCommandEvent::class => 'onConsoleCommand',
            ConsoleTerminateEvent::class => 'onConsoleTerminate',
        );
    }
}
