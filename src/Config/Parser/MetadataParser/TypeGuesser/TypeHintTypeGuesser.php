<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser\MetadataParser\TypeGuesser;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use Reflector;

class TypeHintTypeGuesser extends TypeGuesser
{
    public function getName(): string
    {
        return 'Type Hint';
    }

    public function guessType(ReflectionClass $reflectionClass, Reflector $reflector, array $filterGraphQLTypes = []): ?string
    {
        $type = null;
        $hasDefaultValue = false;

        switch (true) {
            case $reflector instanceof ReflectionParameter:
                /** @var ReflectionParameter $reflector */
                $hasDefaultValue = $reflector->isDefaultValueAvailable();
                // no break
            case $reflector instanceof ReflectionProperty:
                /** @var ReflectionProperty $reflector */
                $type = $reflector->hasType() ? $reflector->getType() : null;

                break;
            case $reflector instanceof ReflectionMethod:
                /** @var ReflectionMethod $reflector */
                $type = $reflector->hasReturnType() ? $reflector->getReturnType() : null;
                break;
        }
        /** @var ReflectionNamedType|null $type */
        if (!$type) {
            throw new TypeGuessingException('No type-hint');
        }

        $sType = $type->getName();
        if ($type->isBuiltin()) {
            $gqlType = $this->resolveTypeFromPhpType($sType);
            if (null === $gqlType) {
                throw new TypeGuessingException(sprintf('No corresponding GraphQL type found for builtin type "%s"', $sType));
            }
        } else {
            $gqlType = $this->map->resolveType($sType, $filterGraphQLTypes);
            if (null === $gqlType) {
                throw new TypeGuessingException(sprintf('No corresponding GraphQL %s found for class "%s"', $filterGraphQLTypes ? implode(',', $filterGraphQLTypes) : 'object', $sType));
            }
        }
        $nullable = $hasDefaultValue || $type->allowsNull();

        return sprintf('%s%s', $gqlType, $nullable ? '' : '!');
    }

    /**
     * Convert a PHP Builtin type to a GraphQL type.
     */
    protected function resolveTypeFromPhpType(string $phpType): ?string
    {
        switch ($phpType) {
            case 'boolean':
            case 'bool':
                return 'Boolean';
            case 'integer':
            case 'int':
                return 'Int';
            case 'float':
            case 'double':
                return 'Float';
            case 'string':
                return 'String';
            default:
                return null;
        }
    }
}
