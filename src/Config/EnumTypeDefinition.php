<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use function array_key_exists;
use function is_array;
use function is_null;

class EnumTypeDefinition extends TypeDefinition
{
    public function getDefinition(): ArrayNodeDefinition
    {
        /** @var ArrayNodeDefinition $node */
        $node = self::createNode('_enum_config');

        /** @phpstan-ignore-next-line */
        $node
            ->children()
                ->append($this->nameSection())
                ->arrayNode('values')
                    ->useAttributeAsKey('name')
                    ->beforeNormalization()
                        ->ifTrue(fn ($v) => is_array($v))
                        ->then(function ($v) {
                            foreach ($v as $name => &$options) {
                                // short syntax NAME: VALUE
                                if (!is_null($options) && !is_array($options)) {
                                    $options = ['value' => $options];
                                }

                                // use name as value if no value given
                                if (!array_key_exists('value', $options)) {
                                    $options['value'] = $name;
                                }
                            }

                            return $v;
                        })
                    ->end()
                    ->prototype('array')
                        ->isRequired()
                        ->children()
                            ->scalarNode('value')->isRequired()->end()
                            ->append($this->descriptionSection())
                            ->append($this->deprecationReasonSection())
                        ->end()
                    ->end()
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                ->end()
                ->append($this->descriptionSection())
            ->end();

        return $node;
    }
}
