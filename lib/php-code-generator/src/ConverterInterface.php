<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator;


interface ConverterInterface
{
    const TYPE_STRING = 'string';
    const TYPE_INT = 'integer';
    const TYPE_BOOL = 'boolean';
    const TYPE_DOUBLE = 'double';
    const TYPE_OBJECT = 'object';
    const TYPE_ARRAY = 'array';

    /**
     *
     * @param $value
     * @return string
     */
    function convert($value): string;

    /**
     * Checks, whether the value should be converted.
     *
     * @param $value
     * @return bool
     */
    function check($value): bool;
}
