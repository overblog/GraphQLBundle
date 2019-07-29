<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

abstract class TypeWithOutputFieldsDefinition extends TypeDefinition
{
    /**
     * @param string $name
     *
     * @return ArrayNodeDefinition|\Symfony\Component\Config\Definition\Builder\NodeDefinition
     */
    protected function outputFieldsSelection(string $name = 'fields')
    {
        $node = self::createNode($name);
        $node
            ->isRequired()
            ->requiresAtLeastOneElement();
        /* @var ArrayNodeDefinition $prototype */
        $prototype = $node->useAttributeAsKey('name', false)->prototype('array');

        $prototype
            ->children()
                ->append($this->typeSelection())
                ->append($this->validationSection(self::VALIDATION_LEVEL_CLASS))
                ->arrayNode('args')
                    ->info('Array of possible type arguments. Each entry is expected to be an array with following keys: name (string), type')
                    ->useAttributeAsKey('name', false)
                    ->prototype('array')
                        // Allow arg type short syntax (Arg: Type => Arg: {type: Type})
                        ->beforeNormalization()
                            ->ifTrue(function ($options) {
                                return \is_string($options);
                            })
                            ->then(function ($options) {
                                return ['type' => $options];
                            })
                        ->end()
                        ->children()
                            ->append($this->typeSelection(true))
                            ->append($this->descriptionSection())
                            ->append($this->defaultValueSection())
                            ->append($this->validationSection(self::VALIDATION_LEVEL_PROPERTY))
                        ->end()
                    ->end()
                ->end()
                ->variableNode('resolve')
                    ->info('Value resolver (expression language can be use here)')
                ->end()
                ->append($this->descriptionSection())
                ->append($this->deprecationReasonSelection())
                ->variableNode('access')
                    ->info('Access control to field (expression language can be use here)')
                ->end()
                ->variableNode('public')
                    ->info('Visibility control to field (expression language can be use here)')
                ->end()
                ->variableNode('complexity')
                    ->info('Custom complexity calculator.')
                ->end()
            ->end();

        return $node;
    }

    protected function fieldsBuilderSection()
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
