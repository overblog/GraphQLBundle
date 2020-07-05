<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @deprecated since 0.13 will be remove in 0.14
 * @codeCoverageIgnore
 */
final class Helper
{
    /**
     * @return TokenInterface|null
     */
    private static function getToken(GlobalVariables $globalVariable)
    {
        if (!$globalVariable->get('container')->has('security.token_storage')) {
            return null;
        }

        return $globalVariable->get('container')->get('security.token_storage')->getToken();
    }

    public static function getUser(GlobalVariables $globalVariable)
    {
        if (!$token = self::getToken($globalVariable)) {
            return null;
        }

        $user = $token->getUser();
        if (!\is_object($user)) {
            return null;
        }

        return $user;
    }
}
