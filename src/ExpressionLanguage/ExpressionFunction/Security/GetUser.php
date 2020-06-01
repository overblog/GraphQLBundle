<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Security\Security;

final class GetUser extends ExpressionFunction
{
    public function __construct(Security $security)
    {
        parent::__construct(
            'getUser',
            static function (): string {
                return '$globalVariables->get(\'security\')->getUser()';
            },
            static function () use ($security) {
                return $security->getUser();
            }
        );
    }
}
