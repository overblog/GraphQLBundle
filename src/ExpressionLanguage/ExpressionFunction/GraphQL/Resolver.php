<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL;

use Overblog\GraphQLBundle\Exception\EvaluatorIsNotAllowedException;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class Resolver extends ExpressionFunction
{
    public function __construct($name = 'resolver')
    {
        parent::__construct(
            $name,
            function (string $alias, string $args = '[]'): string {
                return "\$globalVariable->get('resolverResolver')->resolve([$alias, $args])";
            },
            // This expression function is not designed to be used by it's evaluator
            function () {
                throw new EvaluatorIsNotAllowedException('resolveSingleInputCallback');
            }
        );
    }
}
