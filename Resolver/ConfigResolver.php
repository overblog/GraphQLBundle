<?php

namespace Overblog\GraphBundle\Resolver;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Overblog\GraphBundle\Definition\FieldInterface;
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

    // [name => method]
    private $resolverMap = [
        'fields' => 'resolveFields',
        'isTypeOf' => 'resolveResolveCallback',
        'interfaces' => 'resolveInterfaces',
        'types' => 'resolveTypes',
        'values' => 'resolveValues',
        'resolveType' => 'resolveResolveCallback',
        'resolveCursor' => 'resolveResolveCallback',
        'resolveNode' => 'resolveResolveCallback',
        'nodeType' => 'resolveTypeCallback',
        'connectionFields' => 'resolveFields',
        'edgeFields' => 'resolveFields',
        'outputFields' => 'resolveFields',
        'inputFields' => 'resolveFields',
        'mutateAndGetPayload' => 'resolveResolveCallback',
        'idFetcher' => 'resolveResolveCallback',
        'nodeInterfaceType' => 'resolveTypeCallback',
    ];

    public function __construct(ResolverInterface $typeResolver, ResolverInterface $fieldResolver, ExpressionLanguage $expressionLanguage)
    {
        $this->typeResolver = $typeResolver;
        $this->fieldResolver = $fieldResolver;
        $this->expressionLanguage = $expressionLanguage;
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
            $resolverMethod = $this->resolverMap[$name];
            $values = $this->$resolverMethod($values);
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
                } else {
                    // TODO (mcg-web) throw exception?
                    $options = get_object_vars($fieldsBuilder);
                }

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

            if (isset($options['deprecationReason'])) {
                $options['deprecationReason'] = $this->resolveUsingExpressionLanguageIfNeeded($options['deprecationReason']);
            }
        }

        return $fields;
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
                throw new UnresolvableException(
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

            $types[] = $type;
        }

        return $types;
    }

    private function resolveResolveCallback($expression)
    {
        if (empty($expression)) {
            return null;
        }
        $optionResolver = new OptionsResolver();
        $optionResolver->setDefaults([null, null, null]);

        return function (...$args) use ($expression, $optionResolver) {
            $args = $optionResolver->resolve($args);

            $arg1IsResolveInfo = $args[1] instanceof ResolveInfo;

            return $this->resolveUsingExpressionLanguageIfNeeded(
                $expression,
                [
                    'value' => $args[0],
                    'args' => !$arg1IsResolveInfo ? $args[1] : [],
                    'info' => $arg1IsResolveInfo ? $args[1] : $args[2],
                ]
            );
        };
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
