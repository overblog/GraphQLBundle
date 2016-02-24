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

class FieldResolver extends AbstractResolver
{
    /**
     * @param $alias
     *
     * @return mixed
     */
    public function resolve($alias)
    {
        if (null !== $field = $this->cache->fetch($alias)) {
            return $field;
        }
        $field = $this->getFieldFromAlias($alias);
        $this->cache->save($alias, $field);

        return $field;
    }

    private function getFieldServiceIdFromAlias($alias)
    {
        $typesMapping = $this->container->getParameter('overblog_graphql.fields_mapping');

        if (!isset($typesMapping[$alias]['id'])) {
            throw new UnresolvableException(sprintf('Unknown field with alias "%s" (verified service tag)', $alias));
        }

        return $typesMapping[$alias]['id'];
    }

    private function getFieldFromAlias($alias)
    {
        $serviceId = $this->getFieldServiceIdFromAlias($alias);

        return $serviceId !== null ? $this->container->get($serviceId) : null;
    }
}
