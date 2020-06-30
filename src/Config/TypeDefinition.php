<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config;

use Overblog\GraphQLBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeParentInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

abstract class TypeDefinition
{
    public const VALIDATION_LEVEL_CLASS = 0;
    public const VALIDATION_LEVEL_PROPERTY = 1;

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
            ->ifTrue(fn ($name) => !\preg_match('/^[_a-z][_0-9a-z]*$/i', $name))
            ->thenInvalid('Invalid type name "%s". (see http://spec.graphql.org/June2018/#sec-Names)')
        ->end();

        return $node;
    }

    protected function defaultValueSection()
    {
        return self::createNode('defaultValue', 'variable');
    }

    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    protected function validationSection(int $level): NodeParentInterface
    {
        $node = self::createNode('validation', 'array');

        $node
            // allow shorthands
            ->beforeNormalization()
                ->always(function ($value) {
                    if (\is_string($value)) {
                        // shorthand: cascade or link
                        return 'cascade' === $value ? ['cascade' => null] : ['link' => $value];
                    }

                    if (\is_array($value)) {
                        foreach ($value as $k => $a) {
                            if (!\is_int($k)) {
                                // validation: { link: ... , constraints: ..., cascade: ... }
                                return $value;
                            }
                        }
                        // validation: [list of constraints]
                        return ['constraints' => $value];
                    }

                    return [];
                })
            ->end()
            ->children()
                ->scalarNode('link')
//                    ->defaultNull()
                    ->validate()
                        ->ifTrue(function ($link) use ($level) {
                            if (self::VALIDATION_LEVEL_PROPERTY === $level) {
                                return !\preg_match('/^(?:\\\\?[A-Za-z][A-Za-z\d]+)*[A-Za-z\d]+::(?:[$]?[A-Za-z][A-Za-z_\d]+|[A-Za-z_\d]+\(\))$/m', $link);
                            } else {
                                return !\preg_match('/^(?:\\\\?[A-Za-z][A-Za-z\d]+)*[A-Za-z\d]$/m', $link);
                            }
                        })
                        ->thenInvalid('Invalid link provided: "%s".')
                    ->end()
                ->end()
                ->variableNode('constraints')->end()
            ->end();

        // Add the 'cascade' option if it's a property level validation section
        if (self::VALIDATION_LEVEL_PROPERTY === $level) {
            $node
                ->children()
                    ->arrayNode('cascade')
                        ->children()
                            ->arrayNode('groups')
                                ->beforeNormalization()
                                    ->castToArray()
                                ->end()
                                ->scalarPrototype()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end();
        }

        return $node;
    }

    protected function descriptionSection()
    {
        $node = self::createNode('description', 'scalar');

        return $node;
    }

    protected function deprecationReasonSection()
    {
        $node = self::createNode('deprecationReason', 'scalar');

        $node->info('Text describing why this field is deprecated. When not empty - field will not be returned by introspection queries (unless forced)');

        return $node;
    }

    protected function typeSection($isRequired = false)
    {
        $node = self::createNode('type', 'scalar');

        $node->info('One of internal or custom types.');

        if ($isRequired) {
            $node->isRequired();
        }

        return $node;
    }

    /**
     * @return ArrayNodeDefinition|NodeDefinition
     *
     * @internal
     */
    protected static function createNode(string $name, string $type = 'array')
    {
        return Configuration::getRootNodeWithoutDeprecation(new TreeBuilder($name, $type), $name, $type);
    }
}
