<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use ReflectionClass;
use function sprintf;

final class NewObject extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'newObject',
            function ($className, $args = '[]'): string {
                return \sprintf('(new \ReflectionClass(%s))->newInstanceArgs(%s)', $className, $args);
            },
            function ($arguments, $className, $args): object {
                return new $className(...$args);
            }
        );
    }
}
