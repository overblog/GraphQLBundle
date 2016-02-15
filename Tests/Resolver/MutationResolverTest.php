<?php

namespace Tests\Overblog\GraphQLBundle\Resolver;

use Overblog\GraphQLBundle\Resolver\MutationResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class MutationResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ContainerBuilder */
    private static $container;

    /** @var  MutationResolver */
    private static $mutationResolver;

    public static function setUpBeforeClass()
    {
        $container = new ContainerBuilder();

        $mapping = [
            'Toto' => ['id' => 'overblog_graphql.definition.custom_toto_mutation', 'alias' => 'Toto', 'method' => 'resolveToto'],
            'Tata' => ['id' => 'overblog_graphql.definition.custom_tata_mutation', 'alias' => 'Tata', 'method' => 'resolveTata'],
        ];

        $container->setParameter('overblog_graphql.mutations_mapping', $mapping);

        foreach($mapping as $alias => $options) {
            $container->setDefinition($options['id'], new Definition(sprintf('%s\\%sMutation', __NAMESPACE__, $alias)));
        }

        self::$container = $container;
        self::$mutationResolver = new MutationResolver(self::$container);
    }

    public function testResolveKnownMutation()
    {
        $result = self::$mutationResolver->resolve(['Toto', ['my', 'resolve', 'test']]);

        $this->assertEquals(['my', 'resolve', 'test'], $result);
    }

    /**
     * @expectedException \Overblog\GraphQLBundle\Resolver\UnresolvableException
     */
    public function testResolveUnknownMutation()
    {
        self::$mutationResolver->resolve('Fake');
    }
}

class TotoMutation
{
    public function resolveToto(...$args)
    {
        return $args;
    }
}

class TataMutation
{
    public function resolveTata(...$args)
    {
        return $args;
    }
}
