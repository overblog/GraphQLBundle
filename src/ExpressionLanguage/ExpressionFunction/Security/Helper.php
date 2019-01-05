<?php

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class Helper
{
    /**
     * @param GlobalVariables $globalVariable
     *
     * @return TokenInterface|null
     */
    private static function getToken(GlobalVariables $globalVariable)
    {
        if (!$globalVariable->get('container')->has('security.token_storage')) {
            return;
        }

        return $globalVariable->get('container')->get('security.token_storage')->getToken();
    }

    public static function getUser(GlobalVariables $globalVariable)
    {
        if (!$token = self::getToken($globalVariable)) {
            return;
        }

        $user = $token->getUser();
        if (!\is_object($user)) {
            return;
        }

        return $user;
    }
}
