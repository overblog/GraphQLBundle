<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Resolver;

use ArrayAccess;
use RuntimeException;
use Traversable;
use function array_key_exists;
use function get_class;
use function gettype;
use function is_array;
use function is_object;
use function sprintf;

abstract class ResolverMap implements ResolverMapInterface
{
    private iterable $loadedMap;
    private bool $isMapLoaded = false;
    private array $memorized = [];

    /**
     * Resolvers map.
     *
     * @return mixed
     */
    abstract protected function map();

    private function getLoadedMap(): iterable
    {
        if (!$this->isMapLoaded) {
            $this->checkMap($map = $this->map());
            $this->loadedMap = $map;
            $this->isMapLoaded = true;
        }

        return $this->loadedMap;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $typeName, string $fieldName)
    {
        $loadedMap = $this->getLoadedMap();

        if (!$this->isResolvable($typeName, $fieldName)) {
            throw new UnresolvableException(sprintf('Field "%s.%s" could not be resolved.', $typeName, $fieldName));
        }

        return $loadedMap[$typeName][$fieldName]; // @phpstan-ignore-line
    }

    /**
     * {@inheritdoc}
     */
    public function isResolvable(string $typeName, string $fieldName): bool
    {
        $key = $typeName.'.'.$fieldName;
        if (!isset($this->memorized[$key])) {
            $loadedMap = $this->getLoadedMap();
            $this->memorized[$key] = isset($loadedMap[$typeName]) && array_key_exists($fieldName, $loadedMap[$typeName]); // @phpstan-ignore-line
        }

        return $this->memorized[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function covered(?string $typeName = null)
    {
        $loadedMap = $this->getLoadedMap();
        $covered = [];
        $resolvers = [];
        if (null === $typeName) {
            $resolvers = $loadedMap;
        } elseif (isset($loadedMap[$typeName])) { // @phpstan-ignore-line
            $resolvers = $loadedMap[$typeName];
        }

        foreach ($resolvers as $key => $value) {
            $covered[] = $key;
        }

        return $covered;
    }

    /**
     * @param mixed $map
     */
    private function checkMap($map): void
    {
        if (!is_array($map) && !($map instanceof ArrayAccess && $map instanceof Traversable)) {
            throw new RuntimeException(sprintf(
                '%s::map() should return an array or an instance of \ArrayAccess and \Traversable but got "%s".',
                get_class($this),
                is_object($map) ? get_class($map) : gettype($map)
            ));
        }
    }
}
