<?php

namespace Overblog\GraphQLBundle\Config;

use Overblog\GraphQLBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

abstract class TypeDefinition
{
    abstract public function getDefinition();

    protected function __construct()
    {
    }

    /**
     * @return static
     */
    public static function create()
    {
        return new static();
    }

    protected function resolveTypeSection()
    {
        $node = self::createNode('resolveType', 'variable');

        return $node;
    }

    protected function nameSection()
    {
        $node = self::createNode('name', 'scalar');
        $node->isRequired();
        $node->validate()
            ->ifTrue(function ($name) {
                return !\preg_match('/^[_a-z][_0-9a-z]*$/i', $name);
            })
                ->thenInvalid('Invalid type name "%s". (see https://facebook.github.io/graphql/October2016/#Name)')
        ->end();

        return $node;
    }

    protected function defaultValueSection()
    {
        $node = self::createNode('defaultValue', 'variable');

        return $node;
    }

    protected function descriptionSection()
    {
        $node = self::createNode('description', 'scalar');

        return $node;
    }

    protected function deprecationReasonSelection()
    {
        $node = self::createNode('deprecationReason', 'scalar');

        $node->info('Text describing why this field is deprecated. When not empty - field will not be returned by introspection queries (unless forced)');

        return $node;
    }

    protected function typeSelection($isRequired = false)
    {
        $node = self::createNode('type', 'scalar');

        $node->info('One of internal or custom types.');

        if ($isRequired) {
            $node->isRequired();
        }

        return $node;
    }

    /**
     * @internal
     *
     * @param string $name
     * @param string $type
     *
     * @return \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition|\Symfony\Component\Config\Definition\Builder\NodeDefinition
     */
    protected static function createNode($name, $type = 'array')
    {
        return Configuration::getRootNodeWithoutDeprecation(new TreeBuilder($name, $type), $name, $type);
    }
}
