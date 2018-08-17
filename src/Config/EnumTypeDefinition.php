<?php

namespace Overblog\GraphQLBundle\Config;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class EnumTypeDefinition extends TypeDefinition
{
    public function getDefinition()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('_enum_config');

        $node
            ->children()
                ->append($this->nameSection())
                ->arrayNode('values')
                    ->useAttributeAsKey('name')
                    ->beforeNormalization()
                        ->ifTrue(function ($v) {
                            return is_array($v);
                        })
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
                            ->append($this->deprecationReasonSelection())
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
