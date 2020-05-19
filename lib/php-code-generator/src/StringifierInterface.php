<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator;


interface StringifierInterface
{
    const TYPE_STRING = 'string';
    const TYPE_INT = 'integer';
    const TYPE_BOOL = 'boolean';
    const TYPE_DOUBLE = 'double';
    const TYPE_OBJECT = 'object';

    /**
     *
     * @param $value
     * @return string
     */
    function stringify($value): string;

    /**
     * Checks, whether the values should be stringified.
     *
     * @param $value
     * @return bool
     */
    function check($value): bool;
}
