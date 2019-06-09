<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Event;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @internal
 * TODO(mcg-web): delete hack after migrating Symfony >= 4.3
 */
final class EventDispatcherVersionHelper
{
    public static function isForLegacy(): bool
    {
        return Kernel::VERSION_ID < 40300;
    }

    public static function dispatch(EventDispatcherInterface $dispatcher, $event, ?string $eventName)
    {
        if (self::isForLegacy()) {
            return $dispatcher->dispatch($eventName, $event);
        } else {
            return $dispatcher->dispatch($event, $eventName);
        }
    }
}
