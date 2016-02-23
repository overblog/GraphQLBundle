<?php

namespace Overblog\GraphQLBundle\Resolver;

use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\ArgsInterface;
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

    /**
     * @var ArgResolver
     */
    private $argResolver;

    /** @var array */
    // [name => callable]
    private $resolverMap = [];

    public function __construct(
        ResolverInterface $typeResolver,
        ResolverInterface $fieldResolver,
        ResolverInterface $argResolver,
        ExpressionLanguage $expressionLanguage,
        $enabledDebug = false
    )
    {
        $this->typeResolver = $typeResolver;
        $this->fieldResolver = $fieldResolver;
        $this->argResolver = $argResolver;
        $this->expressionLanguage = $expressionLanguage;
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
        if (!is_array($config) || $config instanceof \ArrayAccess) {
            throw new \RuntimeException('Config must be an array or implement \ArrayAccess interface');
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

                $fieldBuilder = $this->fieldResolver->resolve($alias);
                $builderConfig = isset($options['builderConfig']) ? $this->resolve($options['builderConfig']) : [];
                $builderConfig['name'] = $field;

                if ($fieldBuilder instanceof FieldInterface) {
                    $options = $fieldBuilder->toFieldDefinition($builderConfig);
                } elseif(is_callable($fieldBuilder)) {
                    $options = call_user_func_array($fieldBuilder, [$builderConfig]);
                } elseif(is_object($fieldBuilder)) {
                    $options = get_object_vars($fieldBuilder);
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
                    if (isset($argsOptions['defaultValue'])) {
                        $argsOptions['defaultValue'] = $this->resolveUsingExpressionLanguageIfNeeded($argsOptions['defaultValue']);
                    }
                }
            }

            if (isset($options['argsBuilder'])) {
                $alias = $options['argsBuilder']['name'];

                $argsBuilder = $this->argResolver->resolve($alias);
                $builderConfig = isset($options['args']['builderConfig']) ? $this->resolve($options['args']['builderConfig']) : [];

                $options['args'] = isset($options['args']) ? $options['args'] : [];

                if ($argsBuilder instanceof ArgsInterface) {
                    $options['args'] = array_merge($argsBuilder->toArgsDefinition($builderConfig), $options['args']);
                } elseif(is_callable($argsBuilder)) {
                    $options['args'] = array_merge(call_user_func_array($argsBuilder, [$builderConfig]), $options['args']);
                } elseif(is_object($argsBuilder)) {
                    $options['args'] = array_merge(get_object_vars($argsBuilder), $options['args']);
                } else {
                    throw new \RuntimeException(sprintf('Could not build args "%s".', $alias));
                }

                unset($options['argsBuilder']);
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
        $type = $this->typeResolver->resolve($expr);

        if (class_exists($parentClass) && !$type instanceof $parentClass) {
            throw new \InvalidArgumentException(
                sprintf('Invalid type! Must be instance of "%s"', $parentClass)
            );
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
        return function (...$args) use ($expression) {
            $result = $this->resolveUsingExpressionLanguageIfNeeded(
                $expression,
                $this->resolveResolveCallbackArgs(...$args)
            );

            return $result;
        };
    }

    private function resolveResolveCallbackArgs(...$args)
    {
        $optionResolver = new OptionsResolver();
        $optionResolver->setDefaults([null, null, null]);

        $args = $optionResolver->resolve($args);

        $arg1IsResolveInfo = $args[1] instanceof ResolveInfo;

        $value = $args[0];
        /** @var ResolveInfo $info */
        $info = $arg1IsResolveInfo ? $args[1] : $args[2];
        /** @var Argument $resolverArgs */
        $resolverArgs = new Argument(!$arg1IsResolveInfo ? $args[1] : []);

        return [
            'value' => $value,
            'args' => $resolverArgs,
            'info' => $info,
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
