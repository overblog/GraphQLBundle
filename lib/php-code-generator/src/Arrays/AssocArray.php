<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator\Arrays;

use Murtukov\PHPCodeGenerator\Exception\UnrecognizedValueTypeException;
use Murtukov\PHPCodeGenerator\Utils;

class AssocArray extends AbstractArray
{
    /**
     * @return string
     * @throws UnrecognizedValueTypeException
     */
    public function generate(): string
    {
        return Utils::stringify($this->items, $this->multiline, true, static::getConverters());
    }
}
