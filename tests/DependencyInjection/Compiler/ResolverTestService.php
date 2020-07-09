<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class ResolverTestService.
 */
class ResolverTestService implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function doSomethingWithContainer(): ?object
    {
        return $this->container->get('injected_service')->doSomething(); // @phpstan-ignore-line
    }
}
