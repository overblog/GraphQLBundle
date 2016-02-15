<?php

namespace Overblog\GraphQLBundle\Resolver;

use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\FieldInterface;
use Overblog\GraphQLBundle\Relay\Connection\Output\Connection;
use Overblog\GraphQLBundle\Relay\Connection\Output\Edge;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigResolver implements ResolverInterface
{
    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * @var FieldResolver
     */
    private $fieldResolver;

    /** @var boolean */
    private $enabledDebug;

    /** @var array */
    // [name => callable]
    private $resolverMap = [];

    public function __construct(
        ResolverInterface $typeResolver,
        ResolverInterface $fieldResolver,
        ExpressionLanguage $expressionLanguage,
        $enabledDebug = false
    )
    {
        $this->typeResolver = $typeResolver;
        $this->fieldResolver = $fieldResolver;
        $this->expressionLanguage = $expressionLanguage;
        $this->enabledDebug = $enabledDebug;
        $this->resolverMap = [
            'fields' => [$this, 'resolveFields'],
            'isTypeOf' => [$this, 'resolveResolveCallback'],
            'interfaces' => [$this, 'resolveInterfaces'],
            'types' => [$this, 'resolveTypes'],
            'values' => [$this, 'resolveValues'],
            'resolveType' => [$this, 'resolveResolveCallback'],
            'resolveCursor' => [$this, 'resolveResolveCallback'],
            'resolveNode' => [$this, 'resolveResolveCallback'],
            'nodeType' => [$this, 'resolveTypeCallback'],
            'connectionFields' => [$this, 'resolveFields'],
            'edgeFields' => [$this, 'resolveFields'],
            'mutateAndGetPayload' => [$this, 'resolveResolveCallback'],
            'idFetcher' => [$this, 'resolveResolveCallback'],
            'nodeInterfaceType' => [$this, 'resolveTypeCallback'],
            'inputType' => [$this, 'resolveTypeCallback'],
            'outputType' => [$this, 'resolveTypeCallback'],
            'payloadType' => [$this, 'resolveTypeCallback'],
            'resolveSingleInput' => [$this, 'resolveResolveCallback'],
        ];
    }

    public function addResolverMap($name, callable $resolver)
    {
        $this->resolverMap[$name] = $resolver;
    }

    public function resolve($config)
    {
        if (!is_array($config)) {
            $config = [$config];
        }

        foreach($config as $name => &$values) {
            if (!isset($this->resolverMap[$name]) || empty($values)) {
                continue;
            }
            $values = call_user_func_array($this->resolverMap[$name], [$values]);
        }

        return $config;
    }

    private function resolveFields(array $fields)
    {
        foreach ($fields as $field => &$options) {
            if (isset($options['builder']) && is_string($options['builder'])) {
                $alias = $options['builder'];

                $fieldsBuilder = $this->fieldResolver->resolve($alias);
                $builderConfig = isset($options['builderConfig']) ? $this->resolve($options['builderConfig']) : [];
                $builderConfig['name'] = $field;

                if ($fieldsBuilder instanceof FieldInterface) {
                    $options = $fieldsBuilder->toFieldsDefinition($builderConfig);
                } elseif(is_callable($fieldsBuilder)) {
                    $options = call_user_func_array($fieldsBuilder, [$builderConfig]);
                } elseif(is_object($fieldsBuilder)) {
                    $options = get_object_vars($fieldsBuilder);
                } else {
                    throw new \RuntimeException(sprintf('Could not build field "%s".', $alias));
                }

                unset($options['builderConfig'], $options['builder']);

                continue;
            }

            if (isset($options['type']) && is_string($options['type'])) {
                $options['type'] = $this->resolveTypeCallback($options['type']);
            }

            if (isset($options['args'])) {
                foreach($options['args'] as &$argsOptions) {
                    $argsOptions['type'] = $this->resolveTypeCallback($argsOptions['type']);
                }
            }

            if (isset($options['resolve']) && is_string($options['resolve'])) {
                $options['resolve'] = $this->resolveResolveCallback($options['resolve']);
            }

            if (isset($options['access']) && is_string($options['access'])) {
                $resolveCallback = ['GraphQL\Executor\Executor', 'defaultResolveFn'];

                if (isset($options['resolve']) && is_callable($options['resolve'])) {
                    $resolveCallback = $options['resolve'];
                }

                $options['resolve'] = $this->resolveAccessAndWrapResolveCallback($options['access'], $resolveCallback);

                unset($options['access']);
            }

            if (isset($options['deprecationReason'])) {
                $options['deprecationReason'] = $this->resolveUsingExpressionLanguageIfNeeded($options['deprecationReason']);
            }
        }

        return $fields;
    }

    private function resolveTypeCallback($expr)
    {
        return function () use ($expr) {
            return $this->resolveType($expr);
        };
    }

    private function resolveInterfaces(array $rawInterfaces)
    {
        return $this->resolveTypes($rawInterfaces, 'GraphQL\\Type\\Definition\\InterfaceType');
    }

    private function resolveTypes(array $rawTypes, $parentClass = 'GraphQL\\Type\\Definition\\Type')
    {
        $types = [];

        foreach($rawTypes as $alias) {
            $types[] = $this->resolveType($alias, $parentClass);
        }

        return $types;
    }

    private function resolveType($expr, $parentClass = 'GraphQL\\Type\\Definition\\Type')
    {
        try {
            $type = $this->typeResolver->resolve($expr);

            if (class_exists($parentClass) && !$type instanceof $parentClass) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid type! Must be instance of "%s"', $parentClass)
                );
            }
        } catch (\Exception $e) {
            if ($this->enabledDebug) {
                throw $e;
            }
            throw new \RuntimeException(sprintf('An error occurred while resolving type "%s".', $expr), 0, $e);
        }

        return $type;
    }

    private function resolveAccessAndWrapResolveCallback($expression, callable $resolveCallback = null)
    {
        return function (...$args) use ($expression, $resolveCallback) {
            $result = null !== $resolveCallback  ? call_user_func_array($resolveCallback, $args) : null;

            $values = $this->resolveResolveCallbackArgs(...$args);

            $checkAccess = function($object) use ($expression, $values) {
                try {
                    $access = $this->resolveUsingExpressionLanguageIfNeeded(
                        $expression,
                        array_merge($values, ['object' => $object])
                    );
                } catch(\Exception $e) {
                    if ($this->enabledDebug) {
                        throw $e;
                    }
                    $access = false;
                }

                return $access;
            };

            if (is_array($result) || $result instanceof \ArrayAccess) {
                $result = array_filter(
                    array_map(
                        function($object) use ($checkAccess) {
                            return $checkAccess($object) ? $object : null;
                        },
                        $result
                    )
                );
            } elseif ($result instanceof Connection) {
                $result->edges = array_map(
                    function(Edge $edge) use ($checkAccess) {
                        $edge->node = $checkAccess($edge->node) ? $edge->node : null;

                        return $edge;
                    },
                    $result->edges
                );
            } elseif (!empty($result) && !$checkAccess($result)) {
                $result = null;
            }

            return $result;
        };
    }

    private function resolveResolveCallback($expression)
    {
        if (empty($expression)) {
            return null;
        }

        return function (...$args) use ($expression) {
            try {
                $result = $this->resolveUsingExpressionLanguageIfNeeded(
                    $expression,
                    $this->resolveResolveCallbackArgs(...$args)
                );
            } catch(\Exception $e) {
                if ($this->enabledDebug) {
                    throw $e;
                }
                throw new \RuntimeException('An error occurred while executing resolver.', 0, $e);
            }

            return $result;
        };
    }

    private function resolveResolveCallbackArgs(...$args)
    {
        $optionResolver = new OptionsResolver();
        $optionResolver->setDefaults([null, null, null]);

        $args = $optionResolver->resolve($args);

        $arg1IsResolveInfo = $args[1] instanceof ResolveInfo;

        return [
            'value' => $args[0],
            'args' => !$arg1IsResolveInfo ? $args[1] : [],
            'info' => $arg1IsResolveInfo ? $args[1] : $args[2],
        ];

    }

    private function resolveValues(array $rawValues)
    {
        $values = $rawValues;

        foreach ($values as $name => &$options) {
            if (isset($options['value'])) {
                $options['value'] = $this->resolveUsingExpressionLanguageIfNeeded($options['value']);
            }
        }

        return $values;
    }

    private function resolveUsingExpressionLanguageIfNeeded($expression, array $values = [])
    {
        if (is_string($expression) &&  0 === strpos($expression, '@=')) {
            return $this->expressionLanguage->evaluate(substr($expression, 2), $values);
        }

        return $expression;
    }
}
