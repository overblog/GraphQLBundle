<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator;

use Overblog\GraphQLBundle\Generator\Converter\ExpressionConverter;
use Murtukov\PHPCodeGenerator\Arrays\AssocArray as BaseArray;

/**
 * Extends the default AssocArray to properly convert expressions.
 */
class AssocArray extends BaseArray
{
    /**
     * Mark additional stringifiers to be used by the convertion of array values.
     */
    public static function getStringifiers(): array
    {
        return [ExpressionConverter::class];
    }
}
