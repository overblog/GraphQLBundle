<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;

final class ResolveSingleInputCallback extends ExpressionFunction
{
    public function __construct($name = 'resolveSingleInputCallback')
    {
        parent::__construct(
            $name,
            function ($resolveSingleInput) {
                $code = 'function ($value) use ('.TypeGenerator::USE_FOR_CLOSURES.', $args, $context, $info) { ';
                $code .= 'return '.$resolveSingleInput.'; }';

                return $code;
            }
        );
    }
}
