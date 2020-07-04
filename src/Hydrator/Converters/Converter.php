<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator\Converters;

abstract class Converter
{
    /**
     * Returns the name of the class that performs convertation.
     *
     * By default, this is the fully qualified name of the converter class
     * suffixed with "Converter". You can override this method to change that
     * behavior.
     */
    protected function convertedBy(): string
    {
        return static::class.'Converter';
    }
}
