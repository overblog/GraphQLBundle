<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\DependencyInjection;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class Service extends ExpressionFunction
{
    public function __construct(ContainerInterface $container, $name = 'service')
    {
        parent::__construct(
            $name,
            function (string $serviceId): string {
                return "\$globalVariables->get('container')->get($serviceId)";
            },
            function ($arguments, $serviceId) use ($container): ?object {
                return $container->get($serviceId);
            }
        );
    }
}
