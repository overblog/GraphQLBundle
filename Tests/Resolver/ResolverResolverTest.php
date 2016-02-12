<?php

namespace Tests\Overblog\GraphBundle\Resolver;

use Overblog\GraphBundle\Resolver\ResolverResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ResolverResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ContainerBuilder */
    private static $container;

    /** @var  ResolverResolver */
    private static $resolverResolver;

    public static function setUpBeforeClass()
    {
        $container = new ContainerBuilder();

        $mapping = [
            'Toto' => ['id' => 'overblog_graph.definition.custom_toto_resolver', 'alias' => 'Toto', 'method' => 'resolveToto'],
            'Tata' => ['id' => 'overblog_graph.definition.custom_tata_resolver', 'alias' => 'Tata', 'method' => 'resolveTata'],
        ];

        $container->setParameter('overblog_graph.resolvers_mapping', $mapping);

        foreach($mapping as $alias => $options) {
            $container->setDefinition($options['id'], new Definition(sprintf('%s\\%sResolver', __NAMESPACE__, $alias)));
        }

        self::$container = $container;
        self::$resolverResolver = new ResolverResolver(self::$container);
    }

    public function testResolveKnownResolver()
    {
        $result = self::$resolverResolver->resolve(['Toto', ['my', 'resolve', 'test']]);

        $this->assertEquals(['my', 'resolve', 'test'], $result);
    }

    /**
     * @expectedException \Overblog\GraphBundle\Resolver\UnresolvableException
     */
    public function testResolveUnknownResolver()
    {
        self::$resolverResolver->resolve('Fake');
    }
}

class TotoResolver
{
    public function resolveToto(...$args)
    {
        return $args;
    }
}

class TataResolver
{
    public function resolveTata(...$args)
    {
        return $args;
    }
}
