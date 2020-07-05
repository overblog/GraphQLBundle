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
            fn (string $serviceId) => "$this->globalVars->get('container')->get($serviceId)",
            fn ($arguments, $serviceId) => $container->get($serviceId)
        );
    }
}
