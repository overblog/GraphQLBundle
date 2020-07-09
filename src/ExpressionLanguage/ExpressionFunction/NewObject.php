<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class NewObject extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'newObject',
            fn ($className, $args = '[]') => "(new \\ReflectionClass($className))->newInstanceArgs($args)",
            fn ($arguments, $className, $args = []) => new $className(...$args)
        );
    }
}
