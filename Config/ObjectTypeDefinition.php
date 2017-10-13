<?php

namespace Overblog\GraphQLBundle\Config;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ObjectTypeDefinition extends TypeWithOutputFieldsDefinition
{
    public function getDefinition()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('_object_config');

        $node
            ->children()
                ->append($this->nameSection())
                ->append($this->outputFieldsSelection('fields'))
                ->append($this->descriptionSection())
                ->arrayNode('interfaces')
                    ->prototype('scalar')->info('One of internal or custom interface types.')->end()
                ->end()
                ->variableNode('isTypeOf')->end()
                ->variableNode('resolveField')->end()
                ->variableNode('fieldsDefaultAccess')
                    ->info('Default access control to fields (expression language can be use here)')
                ->end()
                ->variableNode('fieldsDefaultPublic')
                    ->info('Default public control to fields (expression language can be use here)')
                ->end()
            ->end();

        $this->treatFieldsDefaultAccess($node);
        $this->treatFieldsDefaultPublic($node);
        $this->treatResolveField($node);

        return $node;
    }

    /**
     * set empty fields.access with fieldsDefaultAccess values if is set?
     *
     * @param ArrayNodeDefinition $node
     */
    private function treatFieldsDefaultAccess(ArrayNodeDefinition $node)
    {
        $node->validate()
            ->ifTrue(function ($v) {
                return array_key_exists('fieldsDefaultAccess', $v) && null !== $v['fieldsDefaultAccess'];
            })
            ->then(function ($v) {
                foreach ($v['fields'] as &$field) {
                    if (array_key_exists('access', $field) && null !== $field['access']) {
                        continue;
                    }

                    $field['access'] = $v['fieldsDefaultAccess'];
                }

                return $v;
            })
        ->end();
    }

    /**
     * set empty fields.public with fieldsDefaultPublic values if is set?
     *
     * @param ArrayNodeDefinition $node
     */
    private function treatFieldsDefaultPublic(ArrayNodeDefinition $node)
    {
        $node->validate()
            ->ifTrue(function ($v) {
                return array_key_exists('fieldsDefaultPublic', $v) && null !== $v['fieldsDefaultPublic'];
            })
            ->then(function ($v) {
                foreach ($v['fields'] as &$field) {
                    if (array_key_exists('public', $field) && null !== $field['public']) {
                        continue;
                    }

                    $field['public'] = $v['fieldsDefaultPublic'];
                }

                return $v;
            })
        ->end();
    }

    /**
     * resolveField is set as fields default resolver if not set
     * then remove resolveField to keep "access" feature
     * TODO(mcg-web) : get a cleaner way to use resolveField combine with "access" feature.
     *
     * @param ArrayNodeDefinition $node
     */
    private function treatResolveField(ArrayNodeDefinition $node)
    {
        $node->validate()
            ->ifTrue(function ($v) {
                return array_key_exists('resolveField', $v) && null !== $v['resolveField'];
            })
            ->then(function ($v) {
                $resolveField = $v['resolveField'];
                unset($v['resolveField']);
                foreach ($v['fields'] as &$field) {
                    if (!empty($field['resolve'])) {
                        continue;
                    }

                    $field['resolve'] = $resolveField;
                }

                return $v;
            })
        ->end();
    }
}
