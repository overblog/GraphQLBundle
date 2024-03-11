<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\VariableNodeDefinition;

use function is_string;

final class InputObjectTypeDefinition extends TypeDefinition
{
    public function getDefinition(): ArrayNodeDefinition
    {
        /** @var ArrayNodeDefinition $node */
        $node = self::createNode('_input_object_config');

        /** @phpstan-ignore-next-line */
        $node
            ->children()
                ->append($this->nameSection())
                ->append($this->validationSection(self::VALIDATION_LEVEL_CLASS))
                ->arrayNode('fields')
                    ->useAttributeAsKey('name', false)
                    ->prototype('array')
                        // Allow field type short syntax (Field: Type => Field: {type: Type})
                        ->beforeNormalization()
                            ->ifTrue(fn ($options) => is_string($options))
                            ->then(fn ($options) => ['type' => $options])
                        ->end()
                        ->append($this->typeSection(true))
                        ->append($this->descriptionSection())
                        ->append($this->defaultValueSection())
                        ->append($this->publicSection())
                        ->append($this->validationSection(self::VALIDATION_LEVEL_PROPERTY))
                        ->append($this->deprecationReasonSection())
                    ->end()
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                ->end()
                ->append($this->descriptionSection())
            ->end();

        return $node;
    }

    protected function publicSection(): VariableNodeDefinition
    {
        return self::createNode('public', 'variable')
            ->info('Visibility control to field (expression language can be used here)');
    }
}
