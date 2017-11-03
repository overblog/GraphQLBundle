<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class HasAnyRole extends ExpressionFunction
{
    public function __construct($name = 'hasAnyRole')
    {
        parent::__construct(
            $name,
            function ($roles) {
                $code = sprintf('array_reduce(%s, function ($isGranted, $role) use ($container) { return $isGranted || $container->get(\'security.authorization_checker\')->isGranted($role); }, false)', $roles);

                return $code;
            }
        );
    }
}
