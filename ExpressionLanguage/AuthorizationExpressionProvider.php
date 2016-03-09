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
            new ExpressionFunction('hasRole', function () {}, function (array $variables, $role) {
                return $variables['container']->get('security.authorization_checker')->isGranted($role);
            }),

            new ExpressionFunction('hasAnyRole', function () {}, function (array $variables, array $roles) {
                foreach ($roles as $role) {
                    if ($variables['container']->get('security.authorization_checker')->isGranted($role)) {
                        return true;
                    }
                }

                return false;
            }),

            new ExpressionFunction('isAnonymous', function () {}, function (array $variables) {
                return $variables['container']->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_ANONYMOUSLY');
            }),

            new ExpressionFunction('isRememberMe', function () {}, function (array $variables) {
                return $variables['container']->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED');
            }),

            new ExpressionFunction('isFullyAuthenticated', function () {}, function (array $variables) {
                return $variables['container']->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY');
            }),

            new ExpressionFunction('isAuthenticated', function () {}, function (array $variables) {
                return
                    $variables['container']->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')
                    || $variables['container']->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY');
            }),

            new ExpressionFunction('hasPermission', function () {}, function (array $variables, $object, $permission) {
                return $variables['container']->get('security.authorization_checker')->isGranted($permission, $object);
            }),

            new ExpressionFunction('hasAnyPermission', function () {}, function (array $variables, $object, array $permissions) {
                foreach ($permissions as $permission) {
                    if ($variables['container']->get('security.authorization_checker')->isGranted($permission, $object)) {
                        return true;
                    }
                }

                return false;
            }),
        ];
    }
}
