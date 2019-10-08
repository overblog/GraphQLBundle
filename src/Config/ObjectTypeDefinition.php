<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ObjectTypeDefinition extends TypeWithOutputFieldsDefinition
{
    public function getDefinition()
    {
        $node = self::createNode('_object_config');

        $node
            ->children()
                ->append($this->validationSection(self::VALIDATION_LEVEL_CLASS))
                ->append($this->nameSection())
                ->append($this->outputFieldsSelection())
                ->append($this->fieldsBuilderSection())
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

        return $node;
    }

    /**
     * set empty fields.access with fieldsDefaultAccess values if is set?
     *
     * @param ArrayNodeDefinition $node
     */
    private function treatFieldsDefaultAccess(ArrayNodeDefinition $node): void
    {
        $node->validate()
            ->ifTrue(function ($v) {
                return \array_key_exists('fieldsDefaultAccess', $v) && null !== $v['fieldsDefaultAccess'];
            })
            ->then(function ($v) {
                foreach ($v['fields'] as &$field) {
                    if (\array_key_exists('access', $field) && null !== $field['access']) {
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
    private function treatFieldsDefaultPublic(ArrayNodeDefinition $node): void
    {
        $node->validate()
            ->ifTrue(function ($v) {
                return \array_key_exists('fieldsDefaultPublic', $v) && null !== $v['fieldsDefaultPublic'];
            })
            ->then(function ($v) {
                foreach ($v['fields'] as &$field) {
                    if (\array_key_exists('public', $field) && null !== $field['public']) {
                        continue;
                    }
                    $field['public'] = $v['fieldsDefaultPublic'];
                }

                return $v;
            })
        ->end();
    }
}
