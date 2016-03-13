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

abstract class AbstractSimpleResolver extends AbstractResolver
{
    /**
     * @param $alias
     *
     * @return mixed
     */
    public function resolve($alias)
    {
        if (null !== $solution = $this->cache->fetch($alias)) {
            return $solution;
        }
        $solution = $this->getSolution($alias);
        if (null === $solution) {
            throw new UnresolvableException($this->unresolvableMessage($alias));
        }

        $this->cache->save($alias, $solution);

        return $solution;
    }

    abstract protected function unresolvableMessage($alias);
}
