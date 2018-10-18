<?php

namespace Overblog\GraphQLBundle\Resolver;

use GraphQL\Type\Definition\Type;

class TypeResolver extends AbstractResolver
{
    private $cache = [];

    /**
     * @param string $alias
     *
     * @return Type
     */
    public function resolve($alias)
    {
        if (null === $alias) {
            return;
        }

        if (!isset($this->cache[$alias])) {
            $type = $this->string2Type($alias);
            $this->cache[$alias] = $type;
        }

        return $this->cache[$alias];
    }

    private function string2Type($alias)
    {
        if (false !== ($type = $this->wrapTypeIfNeeded($alias))) {
            return $type;
        }

        return $this->baseType($alias);
    }

    private function baseType($alias)
    {
        $type = $this->getSolution($alias);
        if (null === $type) {
            throw new UnresolvableException(
                \sprintf('Could not found type with alias "%s". Do you forget to define it?', $alias)
            );
        }

        return $type;
    }

    private function wrapTypeIfNeeded($alias)
    {
        // Non-Null
        if ('!' === $alias[\strlen($alias) - 1]) {
            return Type::nonNull($this->string2Type(\substr($alias, 0, -1)));
        }
        // List
        if ($this->hasNeedListOfWrapper($alias)) {
            return Type::listOf($this->string2Type(\substr($alias, 1, -1)));
        }

        return false;
    }

    private function hasNeedListOfWrapper($alias)
    {
        if ('[' === $alias[0]) {
            $got = $alias[\strlen($alias) - 1];
            if (']' !== $got) {
                throw new UnresolvableException(
                    \sprintf('Malformed ListOf wrapper type "%s" expected "]" but got "%s".', $alias, \json_encode($got))
                );
            }

            return true;
        }

        return false;
    }

    protected function supportedSolutionClass()
    {
        return Type::class;
    }
}
