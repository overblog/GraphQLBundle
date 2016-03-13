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

class TypeResolver extends AbstractResolver
{
    /**
     * @param string $alias
     *
     * @return \GraphQL\Type\Definition\Type
     */
    public function resolve($alias)
    {
        if (null !== $type = $this->cache->fetch($alias)) {
            return $type;
        }

        $type = $this->getType($alias);

        $this->cache->save($alias, $type);

        return $type;
    }

    private function getType($alias)
    {
        if (!is_string($alias)) {
            return $alias;
        }
        // Non-Null
        if ('!' === $alias[strlen($alias) - 1]) {
            return Type::nonNull($this->getType(substr($alias, 0, -1)));
        }
        // List
        if ('[' === $alias[0]) {
            if (']' !== $alias[strlen($alias) - 1]) {
                throw new UnresolvableException(sprintf('Invalid type "%s"', $alias));
            }

            return Type::listOf($this->getType(substr($alias, 1, -1)));
        }

        $type = $this->getSolution($alias);
        if (null === $type) {
            throw new UnresolvableException(
                sprintf('Unknown type with alias "%s" (verified service tag)', $alias)
            );
        }

        return $type;
    }

    protected function supportedSolutionClass()
    {
        return 'GraphQL\\Type\\Definition\\Type';
    }
}
