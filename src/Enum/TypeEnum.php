<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Enum;

class TypeEnum
{
    public const OBJECT = 'object';
    public const INPUT_OBJECT = 'input-object';
    public const INTERFACE = 'interface';
    public const UNION = 'union';
    public const ENUM = 'enum';
    public const CUSTOM_SCALAR = 'custom-scalar';

    private function __construct()
    {
        // forbid creation of an object
    }
}
