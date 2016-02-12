<?php

namespace Overblog\GraphBundle\Resolver;

class FieldResolver extends AbstractResolver
{
    /**
     * @param $alias
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
        $typesMapping = $this->container->getParameter('overblog_graph.fields_mapping');

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
