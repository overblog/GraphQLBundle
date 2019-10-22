<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

interface TypesConfigurationCallbackInterface
{
    /**
     * @param array            $typesConfiguration
     * @param ContainerBuilder $container
     * @param array            $config
     *
     * @return array
     */
    public static function processTypesConfiguration(array $typesConfiguration, ContainerBuilder $container, array $config): array;
}
