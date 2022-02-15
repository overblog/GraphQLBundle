<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\VariableNodeDefinition;
use function is_array;
use function is_int;
use function is_string;
use function preg_match;

abstract class TypeDefinition
{
    public const VALIDATION_LEVEL_CLASS = 0;
    public const VALIDATION_LEVEL_PROPERTY = 1;

    abstract public function getDefinition(): ArrayNodeDefinition;

    final protected function __construct()
    {
    }

    /**
     * @return static
     */
    public static function create(): self
    {
        return new static();
    }

    protected function nameSection(): ScalarNodeDefinition
    {
        /** @var ScalarNodeDefinition $node */
        $node = self::createNode('name', 'scalar');

        $node
            ->isRequired()
            ->validate()
                ->ifTrue(fn ($name) => !preg_match('/^[_a-z][_0-9a-z]*$/i', $name))
                ->thenInvalid('Invalid type name "%s". (see http://spec.graphql.org/June2018/#sec-Names)')
            ->end()
        ;

        return $node;
    }

    protected function defaultValueSection(): VariableNodeDefinition
    {
        return self::createNode('defaultValue', 'variable');
    }

    protected function validationSection(int $level): ArrayNodeDefinition
    {
        /** @var ArrayNodeDefinition $node */
        $node = self::createNode('validation', 'array');

        /** @phpstan-ignore-next-line */
        $node
            // allow shorthands
            ->beforeNormalization()
                ->always(function ($value) {
                    if (is_string($value)) {
                        // shorthand: cascade or link
                        return 'cascade' === $value ? ['cascade' => null] : ['link' => $value];
                    }

                    if (is_array($value)) {
                        foreach ($value as $k => $a) {
                            if (!is_int($k)) {
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
                    ->validate()
                        ->ifTrue(function ($link) use ($level) {
                            if (self::VALIDATION_LEVEL_PROPERTY === $level) {
                                return !preg_match('/^(?:\\\\?[A-Za-z][A-Za-z\d]+)*[A-Za-z\d]+::(?:[$]?[A-Za-z][A-Za-z_\d]+|[A-Za-z_\d]+\(\))$/m', $link);
                            } else {
                                return !preg_match('/^(?:\\\\?[A-Za-z][A-Za-z\d]+)*[A-Za-z\d]$/m', $link);
                            }
                        })
                        ->thenInvalid('Invalid link provided: "%s".')
                    ->end()
                ->end()
                ->variableNode('constraints')->end()
            ->end();

        // Add the 'cascade' option if it's a property level validation section
        if (self::VALIDATION_LEVEL_PROPERTY === $level) {
            /** @phpstan-ignore-next-line */
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

    protected function descriptionSection(): ScalarNodeDefinition
    {
        /** @var ScalarNodeDefinition $node */
        $node = self::createNode('description', 'scalar');

        return $node;
    }

    protected function deprecationReasonSection(): ScalarNodeDefinition
    {
        /** @var ScalarNodeDefinition $node */
        $node = self::createNode('deprecationReason', 'scalar');

        $node->info('Text describing why this field is deprecated. When not empty - field will not be returned by introspection queries (unless forced)');

        return $node;
    }

    protected function typeSection(bool $isRequired = false): ScalarNodeDefinition
    {
        /** @var ScalarNodeDefinition $node */
        $node = self::createNode('type', 'scalar');

        $node->info('One of internal or custom types.');

        if ($isRequired) {
            $node->isRequired();
        }

        return $node;
    }

    protected function callbackNormalization(NodeDefinition $node, string $new, string $old): void
    {
        $node
            ->beforeNormalization()
                ->ifTrue(fn ($options) => !empty($options[$old]) && empty($options[$new]))
                ->then(function ($options) use ($old, $new) {
                    if (is_callable($options[$old])) {
                        if (is_array($options[$old])) {
                            $options[$new]['function'] = implode('::', $options[$old]);
                        } else {
                            $options[$new]['function'] = $options[$old];
                        }
                    } elseif (is_string($options[$old])) {
                        $options[$new]['expression'] = ExpressionLanguage::stringHasTrigger($options[$old]) ?
                            ExpressionLanguage::unprefixExpression($options[$old]) :
                            json_encode($options[$old]);
                    } else {
                        $options[$new]['expression'] = json_encode($options[$old]);
                    }

                    return $options;
                })
            ->end()
            ->beforeNormalization()
                ->ifTrue(fn ($options) => is_array($options) && array_key_exists($old, $options))
                ->then(function ($options) use ($old) {
                    unset($options[$old]);

                    return $options;
                })
            ->end()
            ->validate()
                ->ifTrue(fn (array $v) => !empty($v[$new]) && !empty($v[$old]))
                ->thenInvalid(sprintf(
                    '"%s" and "%s" should not be used together in "%%s".',
                    $new,
                    $old,
                ))
            ->end()
            ;
    }

    protected function callbackSection(string $name, string $info): ArrayNodeDefinition
    {
        /** @var ArrayNodeDefinition $node */
        $node = self::createNode($name);
        /** @phpstan-ignore-next-line */
        $node
            ->info($info)
            ->validate()
                ->ifTrue(fn (array $v) => !empty($v['function']) && !empty($v['expression']))
                ->thenInvalid('"function" and "expression" should not be use together.')
            ->end()
            ->beforeNormalization()
                // Allow short syntax
                ->ifTrue(fn ($options) => is_string($options) && ExpressionLanguage::stringHasTrigger($options))
                ->then(fn ($options) => ['expression' => ExpressionLanguage::unprefixExpression($options)])
            ->end()
            ->beforeNormalization()
                ->ifTrue(fn ($options) => is_string($options) && !ExpressionLanguage::stringHasTrigger($options))
                ->then(fn ($options) => ['function' => $options])
            ->end()
            ->children()
                ->scalarNode('function')->end()
                ->scalarNode('expression')->end()
            ->end()
        ;

        return $node;
    }

    /**
     * @return mixed
     *
     * @internal
     */
    protected static function createNode(string $name, string $type = 'array')
    {
        return (new TreeBuilder($name, $type))->getRootNode();
    }
}
