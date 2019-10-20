<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config;

class InputObjectTypeDefinition extends TypeDefinition
{
    public function getDefinition()
    {
        $node = self::createNode('_input_object_config');

        $node
            ->children()
                ->append($this->nameSection())
                ->append($this->validationSection(self::VALIDATION_LEVEL_CLASS))
                ->arrayNode('fields')
                    ->useAttributeAsKey('name', false)
                    ->prototype('array')
                        // Allow field type short syntax (Field: Type => Field: {type: Type})
                        ->beforeNormalization()
                            ->ifTrue(function ($options) {
                                return \is_string($options);
                            })
                            ->then(function ($options) {
                                return ['type' => $options];
                            })
                        ->end()
                        ->append($this->typeSelection(true))
                        ->append($this->descriptionSection())
                        ->append($this->defaultValueSection())
                        ->append($this->validationSection(self::VALIDATION_LEVEL_PROPERTY))
                    ->end()
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                ->end()
                ->append($this->descriptionSection())
            ->end();

        return $node;
    }
}
