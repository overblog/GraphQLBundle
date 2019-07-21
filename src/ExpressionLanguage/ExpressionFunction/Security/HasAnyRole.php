<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use function sprintf;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class HasAnyRole extends ExpressionFunction
{
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        parent::__construct(
            'hasAnyRole',
            function ($roles): string {
                $code = sprintf('array_reduce(%s, function ($isGranted, $role) use (%s) { return $isGranted || $globalVariable->get(\'container\')->get(\'security.authorization_checker\')->isGranted($role); }, false)', $roles, TypeGenerator::USE_FOR_CLOSURES);

                return $code;
            },
            function ($arguments, $roles) use ($authorizationChecker): bool {
                return $authorizationChecker->isGranted($roles);
            }
        );
    }
}
