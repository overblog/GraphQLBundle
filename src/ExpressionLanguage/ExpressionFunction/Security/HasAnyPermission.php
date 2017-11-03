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

final class HasAnyPermission extends ExpressionFunction
{
    public function __construct($name = 'hasAnyPermission')
    {
        parent::__construct(
            $name,
            function ($object, $permissions) {
                $code = sprintf('array_reduce(%s, function ($isGranted, $permission) use ($container, $object) { return $isGranted || $container->get(\'security.authorization_checker\')->isGranted($permission, %s); }, false)', $permissions, $object);

                return $code;
            }
        );
    }
}
