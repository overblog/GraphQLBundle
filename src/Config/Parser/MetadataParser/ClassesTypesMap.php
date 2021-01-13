<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser\MetadataParser;

class ClassesTypesMap
{
    /**
     * @var array<string, array{class: string, type: string}>
     */
    protected array $classesMap = [];

    public function hasType(string $gqlType): bool
    {
        return isset($this->classesMap[$gqlType]);
    }

    public function getType(string $gqlType): ?array
    {
        return $this->classesMap[$gqlType] ?? null;
    }

    /**
     * Add a class & a type to the map
     */
    public function addClassType(string $typeName, string $className, string $graphQLType): void
    {
        $this->classesMap[$typeName] = ['class' => $className, 'type' => $graphQLType];
    }

    /**
     * Resolve the type associated with given class name
     */
    public function resolveType(string $className, array $filteredTypes = []): ?string
    {
        foreach ($this->classesMap as $gqlType => $config) {
            if ($config['class'] === $className) {
                if (empty($filteredTypes) || in_array($config['type'], $filteredTypes)) {
                    return $gqlType;
                }
            }
        }

        return null;
    }

    /**
     * Resolve the class name associated with given type
     */
    public function resolveClass(string $typeName): ?string
    {
        return $this->classesMap[$typeName]['class'] ?? null;
    }

    /**
     * Search the classes map for class by predicate.
     */
    public function searchClassesMapBy(callable $predicate, string $type): array
    {
        $classNames = [];
        foreach ($this->classesMap as $gqlType => $config) {
            if ($config['type'] !== $type) {
                continue;
            }

            if ($predicate($gqlType, $config)) {
                $classNames[$gqlType] = $config;
            }
        }

        return $classNames;
    }

    public function toArray(): array
    {
        return $this->classesMap;
    }
}
