<?php

namespace Overblog\GraphQLBundle\Resolver;

class ArgResolver extends AbstractResolver
{
    /**
     * @param $alias
     * @return mixed
     */
    public function resolve($alias)
    {
        if (null !== $arg = $this->cache->fetch($alias)) {
            return $arg;
        }
        $arg = $this->getArgFromAlias($alias);
        $this->cache->save($alias, $arg);

        return $arg;
    }

    private function getArgServiceIdFromAlias($alias)
    {
        $argsMapping = $this->container->getParameter('overblog_graphql.args_mapping');

        if (!isset($argsMapping[$alias]['id'])) {
            throw new UnresolvableException(sprintf('Unknown arg with alias "%s" (verified service tag)', $alias));
        }

        return $argsMapping[$alias]['id'];
    }

    private function getArgFromAlias($alias)
    {
        $serviceId = $this->getArgServiceIdFromAlias($alias);

        return $serviceId !== null ? $this->container->get($serviceId) : null;
    }
}
