<?php

namespace Overblog\GraphQLBundle\Definition\Type;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Schema;
use Overblog\GraphQLBundle\Resolver\Resolver;
use Overblog\GraphQLBundle\Resolver\ResolverMapInterface;

class SchemaDecorator
{
    public function decorate(Schema $schema, ResolverMapInterface $resolverMap)
    {
        foreach ($resolverMap->covered() as $typeName) {
            $type = $schema->getType($typeName);

            if ($type instanceof ObjectType) {
                $this->decorateObjectType($type, $resolverMap);
            } elseif ($type instanceof InterfaceType || $type instanceof UnionType) {
                $this->decorateInterfaceOrUnionType($type, $resolverMap);
            } elseif ($type instanceof EnumType) {
                $this->decorateEnumType($type, $resolverMap);
            } elseif ($type instanceof CustomScalarType) {
                $this->decorateCustomScalarType($type, $resolverMap);
            } else {
                $covered = $resolverMap->covered($type->name);
                if (!empty($covered)) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            '"%s".{"%s"} defined in resolverMap, but type is not managed by SchemaDecorator.',
                            $type->name,
                            implode('", "', $covered)
                        )
                    );
                }
            }
        }
    }

    private function decorateObjectType(ObjectType $type, ResolverMapInterface $resolverMap)
    {
        $fieldsResolved = [];
        foreach ($resolverMap->covered($type->name) as $fieldName) {
            if (ResolverMapInterface::IS_TYPEOF === $fieldName) {
                $this->configTypeMapping($type, $resolverMap, ResolverMapInterface::IS_TYPEOF);
            } elseif (ResolverMapInterface::RESOLVE_FIELD === $fieldName) {
                $resolveFieldFn = Resolver::wrapArgs($resolverMap->resolve($type->name, ResolverMapInterface::RESOLVE_FIELD));
                $type->config[substr(ResolverMapInterface::RESOLVE_FIELD, 2)] = $resolveFieldFn;
                $type->resolveFieldFn = $resolveFieldFn;
            } else {
                $fieldsResolved[] = $fieldName;
            }
        }
        $this->decorateObjectTypeFields($type, $resolverMap, $fieldsResolved);
    }

    /**
     * @param InterfaceType|UnionType $type
     * @param ResolverMapInterface    $resolverMap
     */
    private function decorateInterfaceOrUnionType($type, ResolverMapInterface $resolverMap)
    {
        $this->configTypeMapping($type, $resolverMap, ResolverMapInterface::RESOLVE_TYPE);
        $covered = $resolverMap->covered($type->name);
        if (!empty($covered)) {
            $unknownFields = array_diff($covered, [ResolverMapInterface::RESOLVE_TYPE]);
            if (!empty($unknownFields)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        '"%s".{"%s"} defined in resolverMap, but only "%s" is allowed.',
                        $type->name,
                        implode('", "', $unknownFields),
                        ResolverMapInterface::RESOLVE_TYPE
                    )
                );
            }
        }
    }

    private function decorateCustomScalarType(CustomScalarType $type, ResolverMapInterface $resolverMap)
    {
        static $allowedFields = [
            ResolverMapInterface::SCALAR_TYPE,
            ResolverMapInterface::SERIALIZE,
            ResolverMapInterface::PARSE_VALUE,
            ResolverMapInterface::PARSE_LITERAL,
        ];

        foreach ($allowedFields as $fieldName) {
            $this->configTypeMapping($type, $resolverMap, $fieldName);
        }

        $unknownFields = array_diff($resolverMap->covered($type->name), $allowedFields);
        if (!empty($unknownFields)) {
            throw new \InvalidArgumentException(
                sprintf(
                    '"%s".{"%s"} defined in resolverMap, but only "%s" is allowed.',
                    $type->name,
                    implode('", "', $unknownFields),
                    implode('", "', $allowedFields)
                )
            );
        }
    }

    private function decorateEnumType(EnumType $type, ResolverMapInterface $resolverMaps)
    {
        $fieldNames = [];
        foreach ($type->config['values'] as $key => &$value) {
            $fieldName = isset($value['name']) ? $value['name'] : $key;
            if ($resolverMaps->isResolvable($type->name, $fieldName)) {
                $value['value'] = $resolverMaps->resolve($type->name, $fieldName);
            }
            $fieldNames[] = $fieldName;
        }
        $unknownFields = array_diff($resolverMaps->covered($type->name), $fieldNames);
        if (!empty($unknownFields)) {
            throw new \InvalidArgumentException(
                sprintf(
                    '"%s".{"%s"} defined in resolverMap, was defined in resolvers, but enum is not in schema.',
                    $type->name,
                    implode('", "', $unknownFields)
                )
            );
        }
    }

    private function decorateObjectTypeFields(ObjectType $type, ResolverMapInterface $resolverMap, array $fieldsResolved)
    {
        $fields = $type->config['fields'];

        $decoratedFields = function () use ($fields, $type, $resolverMap, $fieldsResolved) {
            if (is_callable($fields)) {
                $fields = $fields();
            }

            $fieldNames = [];
            foreach ($fields as $key => &$field) {
                $fieldName = isset($field['name']) ? $field['name'] : $key;

                if ($resolverMap->isResolvable($type->name, $fieldName)) {
                    $field['resolve'] = Resolver::wrapArgs($resolverMap->resolve($type->name, $fieldName));
                }

                $fieldNames[] = $fieldName;
            }

            $unknownFields = array_diff($fieldsResolved, $fieldNames);
            if (!empty($unknownFields)) {
                throw new \InvalidArgumentException(
                    sprintf('"%s".{"%s"} defined in resolverMap, but not in schema.', $type->name, implode('", "', $unknownFields))
                );
            }

            return $fields;
        };

        $type->config['fields'] = is_callable($fields) ? $decoratedFields : $decoratedFields();
    }

    private function configTypeMapping(Type $type, ResolverMapInterface $resolverMap, $fieldName)
    {
        if ($resolverMap->isResolvable($type->name, $fieldName)) {
            $type->config[substr($fieldName, 2)] = $resolverMap->resolve($type->name, $fieldName);
        }
    }
}
