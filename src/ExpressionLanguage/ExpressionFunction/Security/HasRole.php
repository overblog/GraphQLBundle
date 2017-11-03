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

final class HasRole extends ExpressionFunction
{
    public function __construct($name = 'hasRole')
    {
        parent::__construct(
            $name,
            function ($role) {
                return sprintf('$container->get(\'security.authorization_checker\')->isGranted(%s)', $role);
            }
        );
    }
}
