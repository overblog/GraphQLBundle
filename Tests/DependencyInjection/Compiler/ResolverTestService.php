<?php

namespace Overblog\GraphQLBundle\Tests\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class ResolverTestService.
 */
class ResolverTestService implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function __construct($service)
    {
    }

    public function doSomethingWithContainer()
    {
        return $this->container->get('injected_service')->doSomething();
    }
}
