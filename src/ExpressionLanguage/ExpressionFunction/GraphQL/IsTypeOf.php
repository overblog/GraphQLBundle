<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class IsTypeOf extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'isTypeOf',
            function ($className) {
                return \sprintf('(($className = %s) && $value instanceof $className)', $className);
            },
            function ($arguments, $className): bool {
                return $className && $arguments['prevValue'] instanceof $className;
            }
        );
    }
}
