<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Functional\App\IsolatedResolver;

use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

final class EchoResolver implements ResolverInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function __invoke($message)
    {
        return $this->container->getParameter('echo.prefix').$message;
    }
}
