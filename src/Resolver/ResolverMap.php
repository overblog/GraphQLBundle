<?php

namespace Overblog\GraphQLBundle\Resolver;

class ResolverMap implements ResolverMapInterface
{
    /** @var array[] */
    private $loadedMap;

    /** @var bool */
    private $isMapLoaded = false;

    /** @var bool[] */
    private $memorized = [];

    /**
     * Resolvers map.
     *
     * @return array<string, callable[]>
     */
    protected function map()
    {
        throw new \LogicException(\sprintf('You must override the %s::map() method.', \get_class($this)));
    }

    private function getLoadedMap()
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
    public function resolve($typeName, $fieldName)
    {
        $loadedMap = $this->getLoadedMap();

        if (!$this->isResolvable($typeName, $fieldName)) {
            throw new UnresolvableException(\sprintf('Field "%s.%s" could not be resolved.', $typeName, $fieldName));
        }

        return $loadedMap[$typeName][$fieldName];
    }

    /**
     * {@inheritdoc}
     */
    public function isResolvable($typeName, $fieldName)
    {
        $key = $typeName.'.'.$fieldName;
        if (!isset($this->memorized[$key])) {
            $loadedMap = $this->getLoadedMap();
            $this->memorized[$key] = isset($loadedMap[$typeName]) && \array_key_exists($fieldName, $loadedMap[$typeName]);
        }

        return $this->memorized[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function covered($typeName = null)
    {
        $loadedMap = $this->getLoadedMap();
        $covered = [];
        $resolvers = [];
        if (null === $typeName) {
            $resolvers = $loadedMap;
        } elseif (isset($loadedMap[$typeName])) {
            $resolvers = $loadedMap[$typeName];
        }

        foreach ($resolvers as $key => $value) {
            $covered[] = $key;
        }

        return $covered;
    }

    private function checkMap($map)
    {
        if (!\is_array($map) && !($map instanceof \ArrayAccess && $map instanceof \Traversable)) {
            throw new \RuntimeException(\sprintf(
                '%s::map() should return an array or an instance of \ArrayAccess and \Traversable but got "%s".',
                \get_class($this),
                \is_object($map) ? \get_class($map) : \gettype($map)
            ));
        }
    }
}
