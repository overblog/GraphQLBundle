<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\Definition\GraphQLServices;
use Stringable;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use function is_object;

/**
 * @deprecated since 0.13 will be remove in 1.0
 * @codeCoverageIgnore
 */
final class Helper
{
    /**
     * @return TokenInterface|null
     */
    private static function getToken(GraphQLServices $services)
    {
        if (!$services->get('container')->has('security.token_storage')) {
            return null;
        }

        return $services->get('container')->get('security.token_storage')->getToken();
    }

    /**
     * @return string|Stringable|UserInterface|null
     */
    public static function getUser(GraphQLServices $services)
    {
        if (!$token = self::getToken($services)) {
            return null;
        }

        $user = $token->getUser();
        if (!is_object($user)) {
            return null;
        }

        return $user;
    }
}
