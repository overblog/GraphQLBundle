<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Resolver;

use GraphQL\Type\Definition\Type;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class TypeResolver extends AbstractResolver
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cacheAdapter;

    public function __construct(CacheItemPoolInterface $cacheAdapter = null)
    {
        $this->cacheAdapter = null !== $cacheAdapter ? $cacheAdapter : new ArrayAdapter(0, false);
    }

    /**
     * @param string $alias
     *
     * @return \GraphQL\Type\Definition\Type
     */
    public function resolve($alias)
    {
        if (null === $alias) {
            return;
        }
        $item = $this->cacheAdapter->getItem(md5($alias));

        if (!$item->isHit()) {
            $type = $this->string2Type($alias);
            $item->set($type);
            $this->cacheAdapter->save($item);
        }

        return $item->get();
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
        if (null !== $type) {
            return $type;
        }

        throw new UnresolvableException(
            sprintf('Unknown type with alias "%s" (verified service tag)', $alias)
        );
    }

    private function wrapTypeIfNeeded($alias)
    {
        // Non-Null
        if ('!' === $alias[strlen($alias) - 1]) {
            return Type::nonNull($this->string2Type(substr($alias, 0, -1)));
        }
        // List
        if ($this->hasNeedListOfWrapper($alias)) {
            return Type::listOf($this->string2Type(substr($alias, 1, -1)));
        }

        return false;
    }

    private function hasNeedListOfWrapper($alias)
    {
        if ('[' === $alias[0]) {
            $got = $alias[strlen($alias) - 1];
            if (']' !== $got) {
                throw new UnresolvableException(
                    sprintf('Malformed ListOf wrapper type "%s" expected "]" but got .', $alias, json_encode($got))
                );
            }

            return true;
        }

        return false;
    }

    protected function postLoadSolution($solution)
    {
        // also add solution with real type name if needed for typeLoader when using autoMapping
        if ($solution && !$this->hasSolution($solution->name)) {
            $this->addSolution($solution->name, function () use ($solution) {
                return $solution;
            });
        }
    }

    protected function supportedSolutionClass()
    {
        return Type::class;
    }
}
