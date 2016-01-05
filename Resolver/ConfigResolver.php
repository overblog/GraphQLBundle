<?php

namespace Overblog\GraphBundle\Resolver;


use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigResolver implements ResolverInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    /**
     * @var ResolverInterface
     */
    private $typeResolver;


    public function __construct(
        ResolverInterface $typeResolver,
        ExpressionLanguage $expressionLanguage,
        ContainerInterface $container
    )
    {
        $this->typeResolver = $typeResolver;
        $this->expressionLanguage = $expressionLanguage;
        $this->container = $container;
    }

    public function resolve($config)
    {
        if (!is_array($config)) {
            $config = [$config];
        }

        if (isset($config['fields'])) {
            foreach ($config['fields'] as $field => &$options) {
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
            }
        }

        if (isset($config['isTypeOf']) && is_string($config['isTypeOf'])) {
            $config['isTypeOf'] = $this->resolveIsTypeOfCallback($config['isTypeOf']);
        }

        if (!empty($config['interfaces'])) {
            $config['interfaces'] = $this->resolveInterfaces($config['interfaces']);
        }

        if (!empty($config['types'])) {
            $config['types'] = $this->resolveTypes($config['types']);
        }

        if (!empty($config['values'])) {
            $config['values'] = $this->resolveValues($config['values']);
        }

        if (isset($config['resolveType']) && is_string($config['resolveType'])) {
            $config['resolveType'] = $this->resolveResolveTypeCallback($config['resolveType']);
        }

        return $config;
    }

    private function resolveTypeCallback($expr)
    {
        return function () use ($expr) {
            $type = $this->typeResolver->resolve($expr);

            if (!$type instanceof Type) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid type! Must be instance of "%s"', 'GraphQL\\Type\\Definition\\Type')
                );
            }

            return $type;
        };
    }

    private function resolveInterfaces(array $rawInterfaces)
    {
        $interfaces = [];

        foreach($rawInterfaces as $alias) {
            $interface = $this->typeResolver->resolve($alias);

            if (!$interface instanceof InterfaceType) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Invalid interface with alias "%s", must extend "%s".',
                        $alias,
                        'GraphQL\\Type\\Definition\\InterfaceType'
                    )
                );
            }

            $interfaces[] = $interface;
        }

        return $interfaces;
    }

    private function resolveTypes(array $rawTypes)
    {
        $types = [];

        foreach($rawTypes as $alias) {
            $type = $this->typeResolver->resolve($alias);

            if (!$type instanceof Type) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Invalid union type with alias "%s", must extend "%s".',
                        $alias,
                        'GraphQL\\Type\\Definition\\Type'
                    )
                );
            }

            $types[] = $type;
        }

        return $types;
    }

    private function resolveResolveCallback($expression)
    {
        if (empty($expression)) {
            return null;
        }

        $container = $this->container;

        return function ($value, array $args, ResolveInfo $info) use ($container, $expression) {

            return $this->expressionLanguage->evaluate(
                $expression,
                [
                    'value' => $value,
                    'args' => $args,
                    'info' => $info,
                    'container' => $container
                ]
            );
        };
    }

    private function resolveResolveTypeCallback($expression)
    {
        if (empty($expression)) {
            return null;
        }

        $container = $this->container;

        return function ($value) use ($container, $expression) {

            return $this->expressionLanguage->evaluate(
                $expression,
                [
                    'value' => $value,
                    'container' => $container
                ]
            );
        };
    }

    private function resolveIsTypeOfCallback($expression)
    {
        if (empty($expression)) {
            return null;
        }

        $container = $this->container;

        return function ($value, ResolveInfo $info) use ($container, $expression) {

            return $this->expressionLanguage->evaluate(
                $expression,
                [
                    'value' => $value,
                    'info' => $info,
                    'container' => $container
                ]
            );
        };
    }

    private function resolveValues(array $rawValues)
    {
        $values = $rawValues;

        foreach ($values as $name => &$options) {
            if (isset($options['value'])) {
                $options['value'] = $this->expressionLanguage->evaluate(
                    $options['value'],
                    [
                        'container' => $this->container
                    ]
                );
            }
        }

        return $values;
    }
}
