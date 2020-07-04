<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator\Converters;

class UppercaseConverter extends Converter
{
    /**
     * @mixed $value
     */
    public function convert(string $value)
    {
        return strtoupper($value);
    }
}
