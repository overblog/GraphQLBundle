<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class FakeCompilerPass.
 */
class FakeCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container
            ->getDefinition('test_resolver')
            ->addArgument(new Reference('injected_service'))
        ;
    }
}
