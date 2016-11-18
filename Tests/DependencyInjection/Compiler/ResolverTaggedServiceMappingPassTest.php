<?php

namespace Overblog\GraphQLBundle\Tests\DependencyInjection\Compiler;

use Overblog\GraphQLBundle\DependencyInjection\Compiler\ResolverTaggedServiceMappingPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Class ResolverTaggedServiceMappingPassTest
 * @package DependencyInjection
 */
class ResolverTaggedServiceMappingPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    public function setUp()
    {
        $container = new ContainerBuilder();
        $container->setDefinition(
            'injected_service',
            new Definition('Overblog\GraphQLBundle\Tests\DependencyInjection\Compiler\FakeInjectedService')
        );

        $container->register(
            'overblog_graphql.resolver_resolver',
            "Overblog\\GraphQLBundle\\Resolver\\ResolverResolver"
        );

        $testResolver = new Definition('Overblog\GraphQLBundle\Tests\DependencyInjection\Compiler\ResolverTestService');
        $testResolver
            ->addTag( 'overblog_graphql.resolver', [
                'alias' => 'test_resolver', 'method' => 'doSomethingWithContainer'
            ]);

        $container->setDefinition('test_resolver', $testResolver);

        $this->container = $container;
    }

    private function addCompilerPassesAndCompile()
    {
            $this->container->addCompilerPass(new ResolverTaggedServiceMappingPass());
            $this->container->addCompilerPass(new FakeCompilerPass());
            $this->container->compile();
    }

    public function testCompilationWorksPassConfigDirective()
    {
        $this->addCompilerPassesAndCompile();

        $this->assertTrue($this->container->has('test_resolver'));
    }
}
