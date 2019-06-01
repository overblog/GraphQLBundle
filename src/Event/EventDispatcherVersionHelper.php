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
    private static $isForLegacy;

    public static function isForLegacy(): bool
    {
        if (null === self::$isForLegacy) {
            self::$isForLegacy = \version_compare(Kernel::VERSION, '4.3.0', '<');
        }

        return self::$isForLegacy;
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
