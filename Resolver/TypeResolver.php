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

use GraphQL\Schema;
use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Resolver\Cache\ArrayCache;
use Overblog\GraphQLBundle\Resolver\Cache\CacheInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class TypeResolver extends AbstractResolver implements ContainerAwareInterface, TypeResolverInterface
{
    use ContainerAwareTrait;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var array
     */
    private $mapping;

    /**
     * @var Schema
     */
    private $schema;

    public function __construct(CacheInterface $cache = null)
    {
        $this->cache = null !== $cache ? $cache : new ArrayCache();
    }

    /**
     * @param array $mapping
     *
     * @return TypeResolver
     */
    public function setMapping(array $mapping)
    {
        $this->mapping = $mapping;

        return $this;
    }

    /**
     * @param Schema $schema
     *
     * @return TypeResolver
     */
    public function setSchema(Schema $schema)
    {
        $this->schema = $schema;
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

        if (null !== $type = $this->cache->fetch($alias)) {
            return $type;
        }

        $type = $this->string2Type($alias);

        $this->cache->save($alias, $type);

        return $type;
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
        $baseTypeResolvers = [
            'baseTypeUsingSchema', // first search in schema if type exists
            'getSolution', // then in register solutions
            'baseTypeUsingContainer', // fallback using mapped services
        ];

        foreach ($baseTypeResolvers as $method) {
            $type = $this->$method($alias);
            if (null !== $type) {
                return $type;
            }
        }

        throw new UnresolvableException(
            sprintf('Unknown type with alias "%s" (verified service tag)', $alias)
        );
    }

    private function baseTypeUsingSchema($alias)
    {
        if (null !== $this->schema) {
            return  $this->schema->getType($alias);
        }
    }

    private function baseTypeUsingContainer($alias)
    {
        if (null !== $this->container && isset($this->mapping[$alias])) {
            $options = $this->mapping[$alias];

            return $this->container->get($options['id']);
        }
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

    protected function supportedSolutionClass()
    {
        return 'GraphQL\\Type\\Definition\\Type';
    }
}
