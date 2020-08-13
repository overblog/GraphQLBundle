<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use function is_string;

abstract class TypeWithOutputFieldsDefinition extends TypeDefinition
{
    protected function outputFieldsSection(): NodeDefinition
    {
        /** @var ArrayNodeDefinition $node */
        $node = self::createNode('fields');

        $node->isRequired()->requiresAtLeastOneElement();

        $prototype = $node->useAttributeAsKey('name', false)->prototype('array');

        /** @phpstan-ignore-next-line */
        $prototype
            ->beforeNormalization()
                // Allow field type short syntax (Field: Type => Field: {type: Type})
                ->ifTrue(fn ($options) => is_string($options))
                ->then(fn ($options) => ['type' => $options])
            ->end()
            ->validate()
                // Remove empty entries
                ->always(function ($value) {
                    if (empty($value['validationGroups'])) {
                        unset($value['validationGroups']);
                    }

                    if (empty($value['args'])) {
                        unset($value['args']);
                    }

                    return $value;
                })
            ->end()
            ->children()
                ->append($this->typeSection())
                ->append($this->validationSection(self::VALIDATION_LEVEL_CLASS))
                ->arrayNode('validationGroups')
                    ->beforeNormalization()
                        ->castToArray()
                    ->end()
                    ->prototype('scalar')
                        ->info('List of validation groups')
                    ->end()
                ->end()
                ->arrayNode('args')
                    ->info('Array of possible type arguments. Each entry is expected to be an array with following keys: name (string), type')
                    ->useAttributeAsKey('name', false)
                    ->prototype('array')
                        // Allow arg type short syntax (Arg: Type => Arg: {type: Type})
                        ->beforeNormalization()
                            ->ifTrue(fn ($options) => is_string($options))
                            ->then(fn ($options) => ['type' => $options])
                        ->end()
                        ->children()
                            ->append($this->typeSection(true))
                            ->append($this->descriptionSection())
                            ->append($this->defaultValueSection())
                            ->append($this->validationSection(self::VALIDATION_LEVEL_PROPERTY))
                        ->end()
                    ->end()
                ->end()
                ->variableNode('resolve')
                    ->info('Value resolver (expression language can be used here)')
                ->end()
                ->append($this->descriptionSection())
                ->append($this->deprecationReasonSection())
                ->variableNode('access')
                    ->info('Access control to field (expression language can be used here)')
                ->end()
                ->variableNode('public')
                    ->info('Visibility control to field (expression language can be used here)')
                ->end()
                ->variableNode('complexity')
                    ->info('Custom complexity calculator.')
                ->end()
            ->end();

        return $node;
    }

    protected function fieldsBuilderSection(): ArrayNodeDefinition
    {
        $node = self::createNode('builders');

        $prototype = $node->prototype('array');

        $prototype
            ->children()
                ->variableNode('builder')->isRequired()->end()
                ->variableNode('builderConfig')->end()
            ->end();

        return $node;
    }
}
