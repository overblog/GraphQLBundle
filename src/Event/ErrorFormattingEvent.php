<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Event;

use ArrayObject;
use GraphQL\Error\Error;
use Symfony\Contracts\EventDispatcher\Event;

final class ErrorFormattingEvent extends Event
{
    private Error $error;
    private ArrayObject $formattedError;

    public function __construct(Error $error, array $formattedError)
    {
        $this->error = $error;
        $this->formattedError = new ArrayObject($formattedError);
    }

    public function getError(): Error
    {
        return $this->error;
    }

    public function getFormattedError(): ArrayObject
    {
        return $this->formattedError;
    }
}
