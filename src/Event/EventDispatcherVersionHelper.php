<?php

namespace Overblog\GraphQLBundle\Event;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @internal
 * TODO(mcg-web): delete hack after migrating Symfony >= 4.3
 */
final class EventDispatcherVersionHelper
{
    /**
     * @return bool
     */
    public static function isForLegacy()
    {
        return Kernel::VERSION_ID < 40300;
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param object                   $event
     * @param string|null              $eventName
     *
     * @return object the event
     */
    public static function dispatch(EventDispatcherInterface $dispatcher, $event, $eventName)
    {
        if (self::isForLegacy()) {
            return $dispatcher->dispatch($eventName, $event);
        } else {
            return $dispatcher->dispatch($event, $eventName);
        }
    }
}
