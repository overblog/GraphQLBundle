<?php

namespace Overblog\GraphQLBundle\Definition\Type\SchemaExtension;

use GraphQL\Type\Definition\CustomScalarType;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Schema;
use Overblog\GraphQLBundle\Resolver\Resolver;
use Overblog\GraphQLBundle\Resolver\ResolverMapInterface;

final class DecoratorExtension implements SchemaExtensionInterface
{
    private $resolverMap;

    public function __construct(ResolverMapInterface $resolverMap)
    {
        $this->resolverMap = $resolverMap;
    }

    public function process(Schema $schema)
    {
        foreach ($this->resolverMap->covered() as $typeName) {
            $type = $schema->getType($typeName);

            if ($type instanceof ObjectType) {
                $this->decorateObjectType($type);
            } elseif ($type instanceof InterfaceType || $type instanceof UnionType) {
                $this->decorateInterfaceOrUnionType($type);
            } elseif ($type instanceof EnumType) {
                $this->decorateEnumType($type);
            } elseif ($type instanceof CustomScalarType) {
                $this->decorateCustomScalarType($type);
            } else {
                $covered = $this->resolverMap->covered($type->name);
                if (!empty($covered)) {
                    throw new \InvalidArgumentException(
                        \sprintf(
                            '"%s".{"%s"} defined in resolverMap, but type is not managed by SchemaDecorator.',
                            $type->name,
                            \implode('", "', $covered)
                        )
                    );
                }
            }
        }
    }

    private function decorateObjectType(ObjectType $type)
    {
        $fieldsResolved = [];
        foreach ($this->resolverMap->covered($type->name) as $fieldName) {
            if (ResolverMapInterface::IS_TYPEOF === $fieldName) {
                $this->configTypeMapping($type, ResolverMapInterface::IS_TYPEOF);
            } elseif (ResolverMapInterface::RESOLVE_FIELD === $fieldName) {
                $resolveFieldFn = Resolver::wrapArgs($this->resolverMap->resolve($type->name, ResolverMapInterface::RESOLVE_FIELD));
                $type->config[\substr(ResolverMapInterface::RESOLVE_FIELD, 2)] = $resolveFieldFn;
                $type->resolveFieldFn = $resolveFieldFn;
            } else {
                $fieldsResolved[] = $fieldName;
            }
        }
        $this->decorateObjectTypeFields($type, $fieldsResolved);
    }

    /**
     * @param InterfaceType|UnionType $type
     */
    private function decorateInterfaceOrUnionType($type)
    {
        $this->configTypeMapping($type, ResolverMapInterface::RESOLVE_TYPE);
        $covered = $this->resolverMap->covered($type->name);
        if (!empty($covered)) {
            $unknownFields = \array_diff($covered, [ResolverMapInterface::RESOLVE_TYPE]);
            if (!empty($unknownFields)) {
                throw new \InvalidArgumentException(
                    \sprintf(
                        '"%s".{"%s"} defined in resolverMap, but only "%s::RESOLVE_TYPE" is allowed.',
                        $type->name,
                        \implode('", "', $unknownFields),
                        ResolverMapInterface::class
                    )
                );
            }
        }
    }

    private function decorateCustomScalarType(CustomScalarType $type)
    {
        static $allowedFields = [
            ResolverMapInterface::SCALAR_TYPE,
            ResolverMapInterface::SERIALIZE,
            ResolverMapInterface::PARSE_VALUE,
            ResolverMapInterface::PARSE_LITERAL,
        ];

        foreach ($allowedFields as $fieldName) {
            $this->configTypeMapping($type, $fieldName);
        }

        $unknownFields = \array_diff($this->resolverMap->covered($type->name), $allowedFields);
        if (!empty($unknownFields)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    '"%s".{"%s"} defined in resolverMap, but only "%s::{%s}" is allowed.',
                    $type->name,
                    \implode('", "', $unknownFields),
                    ResolverMapInterface::class,
                    \implode(', ', ['SCALAR_TYPE', 'SERIALIZE', 'PARSE_VALUE', 'PARSE_LITERAL'])
                )
            );
        }
    }

    private function decorateEnumType(EnumType $type)
    {
        $fieldNames = [];
        foreach ($type->config['values'] as $key => &$value) {
            $fieldName = isset($value['name']) ? $value['name'] : $key;
            if ($this->resolverMap->isResolvable($type->name, $fieldName)) {
                $value['value'] = $this->resolverMap->resolve($type->name, $fieldName);
            }
            $fieldNames[] = $fieldName;
        }
        $unknownFields = \array_diff($this->resolverMap->covered($type->name), $fieldNames);
        if (!empty($unknownFields)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    '"%s".{"%s"} defined in resolverMap, was defined in resolvers, but enum is not in schema.',
                    $type->name,
                    \implode('", "', $unknownFields)
                )
            );
        }
    }

    private function decorateObjectTypeFields(ObjectType $type, array $fieldsResolved)
    {
        $fields = $type->config['fields'];

        $decoratedFields = function () use ($fields, $type, $fieldsResolved) {
            if (\is_callable($fields)) {
                $fields = $fields();
            }

            $fieldNames = [];
            foreach ($fields as $key => &$field) {
                $fieldName = isset($field['name']) ? $field['name'] : $key;

                if ($this->resolverMap->isResolvable($type->name, $fieldName)) {
                    $field['resolve'] = Resolver::wrapArgs($this->resolverMap->resolve($type->name, $fieldName));
                }

                $fieldNames[] = $fieldName;
            }

            $unknownFields = \array_diff($fieldsResolved, $fieldNames);
            if (!empty($unknownFields)) {
                throw new \InvalidArgumentException(
                    \sprintf('"%s".{"%s"} defined in resolverMap, but not in schema.', $type->name, \implode('", "', $unknownFields))
                );
            }

            return $fields;
        };

        $type->config['fields'] = \is_callable($fields) ? $decoratedFields : $decoratedFields();
    }

    private function configTypeMapping(Type $type, $fieldName)
    {
        if ($this->resolverMap->isResolvable($type->name, $fieldName)) {
            $type->config[\substr($fieldName, 2)] = $this->resolverMap->resolve($type->name, $fieldName);
        }
    }
}
