<?php

namespace Overblog\GraphBundle\ExpressionLanguage;

use Overblog\GraphBundle\Relay\Node\GlobalId;
use Overblog\GraphBundle\Resolver\ResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class AuthorizationExpressionProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions()
    {
        return [
            new ExpressionFunction('hasRole', function ($role) {
                return sprintf('$authChecker->isGranted(%s)', $role);
            }, function (array $variables, $role) {
                return $variables['container']->get('security.authorization_checker')->isGranted($role);
            }),

            new ExpressionFunction('hasAnyRole', function (array $roles) {
                $compiler = 'false';
                foreach ($roles as $role) {
                    $compiler .= ' || ';
                    $compiler .= sprintf('$authChecker->isGranted(%s)', $role);
                }
                return $compiler;
            }, function (array $variables, array $roles) {
                foreach ($roles as $role) {
                    if ($variables['container']->get('security.authorization_checker')->isGranted($role)) {
                        return true;
                    }
                }
                return false;
            }),

            new ExpressionFunction('isAnonymous', function () {
                return '$authChecker->isGranted("IS_AUTHENTICATED_ANONYMOUSLY")';
            }, function (array $variables) {
                return $variables['container']->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_ANONYMOUSLY');
            }),

            new ExpressionFunction('isRememberMe', function () {
                return '$authChecker->isGranted("IS_AUTHENTICATED_REMEMBERED")';
            }, function (array $variables) {
                return $variables['container']->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED');
            }),

            new ExpressionFunction('isFullyAuthenticated', function () {
                return sprintf('$authChecker->isGranted("IS_AUTHENTICATED_FULLY")');
            }, function (array $variables) {
                return $variables['container']->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY');
            }),

            new ExpressionFunction('isAuthenticated', function () {
                return '$authChecker->isGranted("IS_AUTHENTICATED_REMEMBERED") ||  $authChecker->isGranted("IS_AUTHENTICATED_FULLY")';
            }, function (array $variables) {
                return
                    $variables['container']->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')
                    || $variables['container']->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY');
            }),

            new ExpressionFunction('hasPermission', function ($object, $permission) {
                return sprintf('$authChecker->isGranted(%s, $object)', $permission);
            }, function (array $variables, $object, $permission) {
                return $variables['container']->get('security.authorization_checker')->isGranted($permission, $object);
            }),
        ];
    }
}
