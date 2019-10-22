<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\DependencyInjection\Compiler;

use Overblog\GraphQLBundle\DependencyInjection\TypesConfigurationCallbackInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class FakeTypesConfigurationCallback.
 */
class FakeTypesConfigurationCallback implements TypesConfigurationCallbackInterface
{
    public static function processTypesConfiguration(array $typesConfiguration, ContainerBuilder $container, array $config): array
    {
        foreach ($typesConfiguration as $type => &$configuration) {
            if ('enum_class' === ($configuration['type'] ?? '')) {
                $configuration['type'] = 'enum';
                unset($configuration['class']);
                $configuration['config']['values'] = [
                    'foo' => ['value' => 'FOO'],
                    'bar' => ['value' => 'BAR', 'description' => 'baz'],
                ];
            }
        }

        return $typesConfiguration;
    }
}
