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

use Symfony\Component\DependencyInjection\ContainerInterface;

class ResolverResolver extends AbstractProxyResolver
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * ResolverResolver constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getSolution($name)
    {
        $solution = parent::getSolution($name);

        if (! $solution)  {
            $typeOptions = $this->getSolutionOptions($name);
            if ($typeOptions and $this->container->has($typeOptions['id'])) {
                $solution = $this->container->get($typeOptions['id']);
            }
        }

        return $solution;
    }

    protected function unresolvableMessage($alias)
    {
        return sprintf('Unknown resolver with alias "%s" (verified service tag)', $alias);
    }
}
