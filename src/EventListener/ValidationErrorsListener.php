<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\EventListener;

use Overblog\GraphQLBundle\Error\InvalidArgumentsError;
use Overblog\GraphQLBundle\Event\ErrorFormattingEvent;

final class ValidationErrorsListener
{
    public function onErrorFormatting(ErrorFormattingEvent $event): void
    {
        $previous = $event->getError()->getPrevious();

        if ($previous && $previous instanceof InvalidArgumentsError) {
            $formattedError = $event->getFormattedError();
            $formattedError['state'] = $previous->toState();
        }
    }
}
