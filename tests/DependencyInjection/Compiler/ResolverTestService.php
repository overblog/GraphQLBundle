<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
