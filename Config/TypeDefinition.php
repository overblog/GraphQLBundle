<?php

namespace Overblog\GraphQLBundle\Config;

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
        $builder = new TreeBuilder();
        $node = $builder->root('resolveType', 'variable');

        return $node;
    }

    protected function nameSection()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('name', 'scalar');
        $node->isRequired();
        $node->validate()
            ->ifTrue(function ($name) {
                return !preg_match('/^[_a-z][_0-9a-z]*$/i', $name);
            })
                ->thenInvalid('Invalid type name "%s". (see https://facebook.github.io/graphql/#Name)')
        ->end();

        return $node;
    }

    protected function defaultValueSection()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('defaultValue', 'variable');

        return $node;
    }

    protected function descriptionSection()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('description', 'scalar');

        return $node;
    }

    protected function deprecationReasonSelection()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('deprecationReason', 'scalar');

        $node->info('Text describing why this field is deprecated. When not empty - field will not be returned by introspection queries (unless forced)');

        return $node;
    }

    protected function typeSelection($isRequired = false)
    {
        $builder = new TreeBuilder();
        $node = $builder->root('type', 'scalar');

        $node->info('One of internal or custom types.');

        if ($isRequired) {
            $node->isRequired();
        }

        return $node;
    }
}
