<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Resolver\Config;

use Overblog\GraphQLBundle\Definition\ArgsInterface;
use Overblog\GraphQLBundle\Definition\FieldInterface;
use Overblog\GraphQLBundle\Error\UserError;
use Overblog\GraphQLBundle\Relay\Connection\Output\Connection;
use Overblog\GraphQLBundle\Relay\Connection\Output\Edge;

class FieldsConfigSolution extends AbstractConfigSolution implements UniqueConfigSolutionInterface
{
    /**
     * @var TypeConfigSolution
     */
    private $typeConfigSolution;

    /**
     * @var ResolveCallbackConfigSolution
     */
    private $resolveCallbackConfigSolution;

    public function __construct(TypeConfigSolution $typeConfigSolution, ResolveCallbackConfigSolution $resolveCallbackConfigSolution)
    {
        $this->typeConfigSolution = $typeConfigSolution;
        $this->resolveCallbackConfigSolution = $resolveCallbackConfigSolution;
    }

    public function solve($values, $config)
    {
        foreach ($values as $field => &$options) {
            if (isset($options['builder']) && is_string($options['builder'])) {
                $alias = $options['builder'];

                $fieldBuilder = $this->configResolver->getFieldResolver()->resolve($alias);
                $builderConfig = [];
                if (isset($options['builderConfig'])) {
                    if (!is_array($options['builderConfig'])) {
                        $options['builderConfig'] = [$options['builderConfig']];
                    }
                    $builderConfig = $this->configResolver->resolve($options['builderConfig']);
                }
                $builderConfig['name'] = $field;

                $access = isset($options['access']) ? $options['access'] : null;

                if ($fieldBuilder instanceof FieldInterface) {
                    $options = $fieldBuilder->toFieldDefinition($builderConfig);
                } elseif (is_callable($fieldBuilder)) {
                    $options = call_user_func_array($fieldBuilder, [$builderConfig]);
                } elseif (is_object($fieldBuilder)) {
                    $options = get_object_vars($fieldBuilder);
                } else {
                    throw new \RuntimeException(sprintf('Could not build field "%s".', $alias));
                }

                $options['access'] = $access;
                $options = $this->resolveResolveAndAccessIfNeeded($options);

                unset($options['builderConfig'], $options['builder']);

                continue;
            }

            if (isset($options['type'])) {
                $options['type'] = $this->typeConfigSolution->solveTypeCallback($options['type']);
            }

            if (isset($options['args'])) {
                foreach ($options['args'] as &$argsOptions) {
                    $argsOptions['type'] = $this->typeConfigSolution->solveTypeCallback($argsOptions['type']);
                    if (isset($argsOptions['defaultValue'])) {
                        $argsOptions['defaultValue'] = $this->solveUsingExpressionLanguageIfNeeded($argsOptions['defaultValue']);
                    }
                }
            }

            if (isset($options['argsBuilder'])) {
                $alias = $options['argsBuilder']['name'];

                $argsBuilder = $this->configResolver->getArgResolver()->resolve($alias);
                $argsBuilderConfig = [];
                if (isset($options['argsBuilder']['config'])) {
                    if (!is_array($options['argsBuilder']['config'])) {
                        $options['argsBuilder']['config'] = [$options['argsBuilder']['config']];
                    }
                    $argsBuilderConfig = $this->configResolver->resolve($options['argsBuilder']['config']);
                }

                $options['args'] = isset($options['args']) ? $options['args'] : [];

                if ($argsBuilder instanceof ArgsInterface) {
                    $options['args'] = array_merge($argsBuilder->toArgsDefinition($argsBuilderConfig), $options['args']);
                } elseif (is_callable($argsBuilder)) {
                    $options['args'] = array_merge(call_user_func_array($argsBuilder, [$argsBuilderConfig]), $options['args']);
                } elseif (is_object($argsBuilder)) {
                    $options['args'] = array_merge(get_object_vars($argsBuilder), $options['args']);
                } else {
                    throw new \RuntimeException(sprintf('Could not build args "%s".', $alias));
                }

                unset($options['argsBuilder']);
            }

            $options = $this->resolveResolveAndAccessIfNeeded($options);

            if (isset($options['deprecationReason'])) {
                $options['deprecationReason'] = $this->solveUsingExpressionLanguageIfNeeded($options['deprecationReason']);
            }
        }

        return $values;
    }

    private function resolveResolveAndAccessIfNeeded(array $options)
    {
        $treatedOptions = $options;

        if (isset($treatedOptions['resolve'])) {
            $treatedOptions['resolve'] = $this->resolveCallbackConfigSolution->solve($treatedOptions['resolve']);
        }

        if (isset($treatedOptions['access'])) {
            $resolveCallback = $this->configResolver->getDefaultResolveFn();

            if (isset($treatedOptions['resolve'])) {
                $resolveCallback = $treatedOptions['resolve'];
            }

            $treatedOptions['resolve'] = $this->resolveAccessAndWrapResolveCallback($treatedOptions['access'], $resolveCallback);
        }
        unset($treatedOptions['access']);

        return $treatedOptions;
    }

    private function resolveAccessAndWrapResolveCallback($expression, callable $resolveCallback = null)
    {
        return function () use ($expression, $resolveCallback) {
            $args = func_get_args();

            $result = null !== $resolveCallback  ? call_user_func_array($resolveCallback, $args) : null;

            $values = call_user_func_array([$this, 'resolveResolveCallbackArgs'], $args);

            $checkAccess = function ($object, $throwException = false) use ($expression, $values) {
                try {
                    $access = $this->solveUsingExpressionLanguageIfNeeded(
                        $expression,
                        array_merge($values, ['object' => $object])
                    );
                } catch (\Exception $e) {
                    $access = false;
                }

                if ($throwException && !$access) {
                    throw new UserError('Access denied to this field.');
                }

                return $access;
            };

            switch (true) {
                case is_array($result) || $result instanceof \ArrayAccess:
                    $result = array_filter(
                        array_map(
                            function ($object) use ($checkAccess) {
                                return $checkAccess($object) ? $object : null;
                            },
                            $result
                        )
                    );
                    break;

                case $result instanceof Connection:
                    $result->edges = array_map(
                        function (Edge $edge) use ($checkAccess) {
                            $edge->node = $checkAccess($edge->node) ? $edge->node : null;

                            return $edge;
                        },
                        $result->edges
                    );
                    break;

                default:
                    $checkAccess($result, true);
                    break;
            }

            return $result;
        };
    }
}
