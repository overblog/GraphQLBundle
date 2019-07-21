<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Validator;

use Overblog\GraphQLBundle\Event\ErrorFormattingEvent;
use Overblog\GraphQLBundle\Exception\ArgumentsValidationException;

/**
 * @author Timur Murtukov <murtukov@gmail.com>
 */
class Formatter
{
    public function onErrorFormatting(ErrorFormattingEvent $event): void
    {
        $error = $event->getError()->getPrevious();

        if($error instanceof ArgumentsValidationException)
        {
            $state = [];
            $code  = [];

            $violations = $error->getViolations();
            foreach ($violations as $violation) {
                $state[$violation->getPropertyPath()][] = $violation->getMessage();
                $code[$violation->getPropertyPath()][] = $violation->getCode();
            }

            $formattedError = $event->getFormattedError();
            $formattedError->offsetSet('state', $state);
            $formattedError->offsetSet('code', $code);
            $formattedError->offsetUnset('locations');
        }
    }

}
