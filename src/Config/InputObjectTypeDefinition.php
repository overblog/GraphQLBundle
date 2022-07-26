<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use function is_string;

class InputObjectTypeDefinition extends TypeDefinition
{
    public const CONFIG_NAME = '_input_object_config';

    public static function getName(): string
    {
        return static::CONFIG_NAME;
    }

    public function getDefinition(): ArrayNodeDefinition
    {
        /** @var ArrayNodeDefinition $node */
        $node = self::createNode(static::CONFIG_NAME);

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
