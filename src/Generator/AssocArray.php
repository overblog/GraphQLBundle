<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator;

use Murtukov\PHPCodeGenerator\Arrays\AssocArray as BaseArray;
use Overblog\GraphQLBundle\Generator\Converter\ExpressionConverter;

/**
 * Extends the default AssocArray to properly convert expressions.
 */
class AssocArray extends BaseArray
{
    /**
     * Mark additional converters to be used by convertion of array values.
     */
    public static function getConverters(): array
    {
        return [ExpressionConverter::class];
    }
}
