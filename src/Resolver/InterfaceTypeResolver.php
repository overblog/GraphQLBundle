<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Resolver;

class InterfaceTypeResolver
{
    private TypeResolver $typeResolver;
    private array $interfacesMap;

    public function __construct(TypeResolver $typeResolver, array $interfacesMap = [])
    {
        $this->typeResolver = $typeResolver;
        $this->interfacesMap = $interfacesMap;
    }

    public function resolveType(string $interfaceType, mixed $value)
    {
        if (!isset($this->interfacesMap[$interfaceType])) {
            throw new UnresolvableException(sprintf('Default interface type resolver was unable to find interface with name "%s"', $interfaceType));
        }

        $gqlType = null;
        $types = $this->interfacesMap[$interfaceType];
        foreach ($types as $className => $type) {
            if ($value instanceof $className) {
                $gqlType = $type;
                break;
            }
        }

        if (null === $gqlType) {
            throw new UnresolvableException(sprintf('Default interface type resolver with interface "%s" did not find a matching instance in: %s', $interfaceType, implode(', ', array_keys($types))));
        }

        return $this->typeResolver->resolve($gqlType);
    }
}
