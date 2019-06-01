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
    private static $isForLegacy;

    /**
     * @return bool
     */
    public static function isForLegacy()
    {
        if (null === self::$isForLegacy) {
            self::$isForLegacy = \version_compare(Kernel::VERSION, '4.3.0', '<');
        }

        return self::$isForLegacy;
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
