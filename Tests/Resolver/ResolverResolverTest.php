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
            'Toto' => 'overblog_graph.definition.custom_toto_resolver',
            'Tata' => 'overblog_graph.definition.custom_tata_resolver',
        ];

        $container->setParameter('overblog_graph.resolvers_mapping', $mapping);

        foreach($mapping as $alias => $id) {
            $container->setDefinition($id, new Definition('stdClass'))
                ->setProperty('name', $alias);
        }

        self::$container = $container;
        self::$resolverResolver = new ResolverResolver(self::$container);
    }

    public function testResolveKnownField()
    {
        $resolver = self::$resolverResolver->resolve('Toto');

        $this->assertInstanceOf('stdClass', $resolver);
        $this->assertEquals('Toto', $resolver->name);
    }

    /**
     * @expectedException \Overblog\GraphBundle\Resolver\UnresolvableException
     */
    public function testResolveUnknownField()
    {
        self::$resolverResolver->resolve('Fake');
    }
}
