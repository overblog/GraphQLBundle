<?php

namespace Overblog\GraphQLBundle\Tests\DependencyInjection\Compiler;

use Overblog\GraphQLBundle\DependencyInjection\Compiler\ResolverTaggedServiceMappingPass;
use Overblog\GraphQLBundle\Resolver\ResolverResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Class ResolverTaggedServiceMappingPassTest.
 */
class ResolverTaggedServiceMappingPassTest extends TestCase
{
    /** @var ContainerBuilder */
    private $container;

    public function setUp()
    {
        $container = new ContainerBuilder();
        $container->setDefinition('injected_service', new Definition(FakeInjectedService::class));

        $container->register('overblog_graphql.resolver_resolver', ResolverResolver::class);

        $testResolver = new Definition(ResolverTestService::class);
        $testResolver
            ->addTag('overblog_graphql.resolver', [
                'alias' => 'test_resolver', 'method' => 'doSomethingWithContainer',
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
