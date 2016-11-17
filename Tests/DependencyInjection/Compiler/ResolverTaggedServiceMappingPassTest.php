<?php

namespace Overblog\GraphQLBundle\Tests\DependencyInjection\Compiler;

use Overblog\GraphQLBundle\DependencyInjection\Compiler\ResolverTaggedServiceMappingPass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

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
        $container->setDefinition('injected_service', new Definition(FakeInjectedService::class));
        $container->register(
            'overblog_graphql.resolver_resolver',
            "Overblog\\GraphQLBundle\\Resolver\\ResolverResolver"
        );

        $fakeResolver = new Definition(FakeResolverService::class);
        $fakeResolver
            ->addTag( 'overblog_graphql.resolver', [
                'alias' => 'fake_resolver', 'method' => 'doSomethingWithContainer'
            ]);

        $container->setDefinition('fake_resolver', $fakeResolver);

        $this->container = $container;
    }

    private function addCompilerPassesAndCompile()
    {
            $this->container->addCompilerPass(new ResolverTaggedServiceMappingPass());
            // Manipulate container after ResolverTaggedServiceMappingPass
            $this->container->addCompilerPass(new FakeCompilerPass());
            $this->container->compile();
    }

    public function testCompilationWorksPassConfigDirective()
    {
        $this->addCompilerPassesAndCompile();

        $this->assertTrue($this->container->has('fake_resolver'));
    }
}

class FakeCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $container
            ->getDefinition('fake_resolver')
            ->addArgument(new Reference('injected_service'))
        ;
    }
}

// Instantiate ContainerAwareInterface to trigger the problem in ResolverTaggedServiceMappingPass
class FakeResolverService implements ContainerAwareInterface
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

class FakeInjectedService
{
    public function doSomething()
    {
        return true;
    }
}
