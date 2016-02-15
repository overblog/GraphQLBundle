<?php

namespace Overblog\GraphQLBundle\Resolver;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResolverResolver extends AbstractResolver
{
    /**
     * @param $input
     * @return mixed
     */
    public function resolve($input)
    {
        if (!is_array($input)) {
            $input = [$input];
        }

        if (!isset($input[0]) || !isset($input[1])) {
            $optionResolver = new OptionsResolver();
            $optionResolver->setDefaults([null, []]);
            $input = $optionResolver->resolve($input);
        }

        $alias = $input[0];
        $funcArgs = $input[1];

        if (null === $func = $this->cache->fetch($alias)) {
            $options = $this->getResolverServiceOptionsFromAlias($alias);

            $resolver = $this->container->get($options['id']);
            if ($resolver instanceof ContainerAwareInterface) {
                $resolver->setContainer($this->container);
            }
            $func = [$resolver, $options['method']];

            $this->cache->save($alias, $func);
        }

        return call_user_func_array($func, $funcArgs);
    }

    private function getResolverServiceOptionsFromAlias($alias)
    {
        $resolversMapping = $this->getMapping();

        if (!isset($resolversMapping[$alias])) {
            throw new UnresolvableException(
                $this->unresolvableMessage($alias)
            );
        }

        return $resolversMapping[$alias];
    }

    protected function getMapping()
    {
        return $this->container->getParameter('overblog_graphql.resolvers_mapping');
    }

    protected function unresolvableMessage($alias)
    {
        return sprintf('Unknown resolver with alias "%s" (verified service tag)', $alias);
    }
}
