<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

final class GetUser extends ExpressionFunction
{
    public function __construct(Security $security)
    {
        parent::__construct(
            'getUser',
            function (): string {
                return \sprintf('\%s::getUser($globalVariable)', Helper::class);
            },
            function () use ($security): ?UserInterface {
                return $security->getUser();
            }
        );
    }
}
