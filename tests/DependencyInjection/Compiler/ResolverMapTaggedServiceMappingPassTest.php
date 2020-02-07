<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\DependencyInjection\Compiler;

use Overblog\GraphQLBundle\DependencyInjection\Compiler\ResolverMapTaggedServiceMappingPass;
use Overblog\GraphQLBundle\EventListener\TypeDecoratorListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Reference;

final class ResolverMapTaggedServiceMappingPassTest extends TestCase
{
    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('overblog_graphql.resolver_maps', [
            'foo' => [],
            'bar' => [],
        ]);

        $container->register(TypeDecoratorListener::class);
        $container->register('App\\GraphQl\\Resolver\\ResolverMap1')
            ->addTag('overblog_graphql.resolver_map', ['schema' => 'foo']);

        $container->register('App\\GraphQl\\Resolver\\ResolverMap2')
            ->addTag('overblog_graphql.resolver_map', ['schema' => 'foo', 'priority' => 10])
            ->addTag('overblog_graphql.resolver_map', ['schema' => 'bar', 'priority' => -10]);

        $container->register('App\\GraphQl\\Resolver\\ResolverMap3')
            ->addTag('overblog_graphql.resolver_map', ['schema' => 'foo', 'priority' => -10])
            ->addTag('overblog_graphql.resolver_map', ['schema' => 'bar', 'priority' => 10]);

        $container->register('App\\GraphQl\\Resolver\\ResolverMap4')
            ->addTag('overblog_graphql.resolver_map', ['schema' => 'bar']);

        (new ResolverMapTaggedServiceMappingPass())->process($container);

        $typeDecoratorListenerDefinition = $container->getDefinition(TypeDecoratorListener::class);

        $methodCalls = $typeDecoratorListenerDefinition->getMethodCalls();

        $this->assertCount(2, $methodCalls);

        $this->assertSame('addSchemaResolverMaps', $methodCalls[0][0]);
        $this->assertSame('foo', $methodCalls[0][1][0]);
        $this->assertEquals([
            new Reference('App\\GraphQl\\Resolver\\ResolverMap2'),
            new Reference('App\\GraphQl\\Resolver\\ResolverMap1'),
            new Reference('App\\GraphQl\\Resolver\\ResolverMap3'),
        ], $methodCalls[0][1][1]);

        $this->assertSame('addSchemaResolverMaps', $methodCalls[1][0]);
        $this->assertSame('bar', $methodCalls[1][1][0]);
        $this->assertEquals([
            new Reference('App\\GraphQl\\Resolver\\ResolverMap3'),
            new Reference('App\\GraphQl\\Resolver\\ResolverMap4'),
            new Reference('App\\GraphQl\\Resolver\\ResolverMap2'),
        ], $methodCalls[1][1][1]);
    }

    public function testProcessThrowsIfSchemaAttributeIsNotDefinedOnTag(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('overblog_graphql.resolver_maps', []);

        $container->register(TypeDecoratorListener::class);
        $container->register('App\\GraphQl\\Resolver\\ResolverMap')
            ->addTag('overblog_graphql.resolver_map');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The "schema" attribute on the "overblog_graphql.resolver_map" tag of the "App\\GraphQl\\Resolver\\ResolverMap" service is required.');

        (new ResolverMapTaggedServiceMappingPass())->process($container);
    }

    public function testProcessWithResolverMapBothTaggedAndInConfigDoesNotAddItTwice(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('overblog_graphql.resolver_maps', [
            'foo' => [
                'App\\GraphQl\\Resolver\\ResolverMap1' => -10,
                'App\\GraphQl\\Resolver\\ResolverMap2' => 0,
            ],
        ]);

        $container->register(TypeDecoratorListener::class);
        $container->register('App\\GraphQl\\Resolver\\ResolverMap1')
            ->addTag('overblog_graphql.resolver_map', ['schema' => 'foo', 'priority' => 10]);

        $container->register('App\\GraphQl\\Resolver\\ResolverMap2');

        (new ResolverMapTaggedServiceMappingPass())->process($container);

        $typeDecoratorListenerDefinition = $container->getDefinition(TypeDecoratorListener::class);

        $methodCalls = $typeDecoratorListenerDefinition->getMethodCalls();

        $this->assertCount(1, $methodCalls);
        $this->assertSame('foo', $methodCalls[0][1][0]);
        $this->assertEquals([
            new Reference('App\\GraphQl\\Resolver\\ResolverMap1'),
            new Reference('App\\GraphQl\\Resolver\\ResolverMap2'),
        ], $methodCalls[0][1][1]);
    }

    public function testProcessThrowsIfTagReferencesUnknownSchema(): void
    {
        $container = new ContainerBuilder();
        $container->register(TypeDecoratorListener::class);
        $container->setParameter('overblog_graphql.resolver_maps', [
            'foo' => [],
            'bar' => [],
        ]);

        $container->register('App\\GraphQl\\Resolver\\ResolverMap')
            ->addTag('overblog_graphql.resolver_map', ['schema' => 'baz']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Service "App\\GraphQl\\Resolver\\ResolverMap" is invalid: schema "baz" specified on the tag "overblog_graphql.resolver_map" does not exist (known ones are: "foo", "bar").');

        (new ResolverMapTaggedServiceMappingPass())->process($container);
    }
}
