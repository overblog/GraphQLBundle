<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Security\Security;
use Symfony\Component\Security\Core\User\UserInterface;

final class GetUser extends ExpressionFunction
{
    public function __construct(Security $security)
    {
        parent::__construct(
            'getUser',
            static function (): string {
                return '$globalVariable->get(\'security\')->getUser()';
            },
            static function () use ($security): ?UserInterface {
                return $security->getUser();
            }
        );
    }
}
