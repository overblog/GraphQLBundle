<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator;

use Murtukov\PHPCodeGenerator\Collection as BaseCollection;
use Overblog\GraphQLBundle\Generator\Converter\ExpressionConverter;

/**
 * Extends the default Collection to properly convert expressions.
 */
final class Collection extends BaseCollection
{
    /**
     * Mark converters to be used by conversion of array values.
     */
    protected array $converters = [ExpressionConverter::class];
}
