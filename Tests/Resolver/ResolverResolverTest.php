<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Overblog\GraphQLBundle\Resolver;

use Overblog\GraphQLBundle\Resolver\ResolverResolver;
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
            'Toto' => ['id' => 'overblog_graphql.definition.custom_toto_resolver', 'alias' => 'Toto', 'method' => 'resolveToto'],
            'Tata' => ['id' => 'overblog_graphql.definition.custom_tata_resolver', 'alias' => 'Tata', 'method' => 'resolveTata'],
        ];

        $container->setParameter('overblog_graphql.resolvers_mapping', $mapping);

        foreach ($mapping as $alias => $options) {
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
     * @expectedException \Overblog\GraphQLBundle\Resolver\UnresolvableException
     */
    public function testResolveUnknownResolver()
    {
        self::$resolverResolver->resolve('Fake');
    }
}

class TotoResolver
{
    public function resolveToto()
    {
        return func_get_args();
    }
}

class TataResolver
{
    public function resolveTata()
    {
        return func_get_args();
    }
}
