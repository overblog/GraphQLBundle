<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class AuthorizationExpressionProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions()
    {
        return [
            new ExpressionFunction(
                'hasRole',
                function ($role) {
                    return sprintf('$container->get(\'security.authorization_checker\')->isGranted(%s)', $role);
                },
                function () {
                }
            ),

            new ExpressionFunction(
                'hasAnyRole',
                function ($roles) {
                    $code = sprintf('array_reduce(%s, function ($isGranted, $role) use ($container) { return $isGranted || $container->get(\'security.authorization_checker\')->isGranted($role); }, false)', $roles);

                    return $code;
                },
                function () {
                }
            ),

            new ExpressionFunction(
                'isAnonymous',
                function () {
                    return '$container->get(\'security.authorization_checker\')->isGranted(\'IS_AUTHENTICATED_ANONYMOUSLY\')';
                },
                function () {
                }
            ),

            new ExpressionFunction(
                'isRememberMe',
                function () {
                    return '$container->get(\'security.authorization_checker\')->isGranted(\'IS_AUTHENTICATED_REMEMBERED\')';
                },
                function () {
                }
            ),

            new ExpressionFunction(
                'isFullyAuthenticated',
                function () {
                    return '$container->get(\'security.authorization_checker\')->isGranted(\'IS_AUTHENTICATED_FULLY\')';
                },
                function () {
                }
            ),

            new ExpressionFunction(
                'isAuthenticated',
                function () {
                    return '$container->get(\'security.authorization_checker\')->isGranted(\'IS_AUTHENTICATED_REMEMBERED\') || $container->get(\'security.authorization_checker\')->isGranted(\'IS_AUTHENTICATED_FULLY\')';
                },
                function () {
                }
            ),

            new ExpressionFunction(
                'hasPermission',
                function ($object, $permission) {
                    $code = sprintf('$container->get(\'security.authorization_checker\')->isGranted(%s, %s)', $permission, $object);

                    return $code;
                },
                function () {
                }
            ),

            new ExpressionFunction(
                'hasAnyPermission',
                function ($object, $permissions) {
                    $code = sprintf('array_reduce(%s, function ($isGranted, $permission) use ($container, $object) { return $isGranted || $container->get(\'security.authorization_checker\')->isGranted($permission, %s); }, false)', $permissions, $object);

                    return $code;
                },
                function () {
                }
            ),
        ];
    }
}
