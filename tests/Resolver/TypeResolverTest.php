<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Resolver;

use GraphQL\Type\Definition\ObjectType;
use Overblog\GraphQLBundle\Resolver\TypeResolver;
use Overblog\GraphQLBundle\Resolver\UnresolvableException;

class TypeResolverTest extends AbstractResolverTest
{
    protected function createResolver(): TypeResolver
    {
        return new TypeResolver();
    }

    protected function getResolverSolutionsMapping(): array
    {
        return [
            'Toto' => ['factory' => fn () => $this->createObjectType(['name' => 'Toto']), 'aliases' => ['foo']],
            'Tata' => ['factory' => fn () => $this->createObjectType(['name' => 'Tata']), 'aliases' => ['bar']],
        ];
    }

    public function createObjectType(array $config): ObjectType
    {
        return new ObjectType($config);
    }

    public function testResolveKnownType(): void
    {
        $type = $this->resolver->resolve('Toto');

        $this->assertInstanceOf(ObjectType::class, $type);
        $this->assertSame('Toto', $type->name);
    }

    public function testResolveUnknownType(): void
    {
        $this->expectException(UnresolvableException::class);
        $this->resolver->resolve('Fake');
    }

    public function testAliases(): void
    {
        $this->assertSame(
            $this->resolver->resolve('Tata'),
            $this->resolver->resolve('bar')
        );
        $this->assertSame(
            $this->resolver->getSolution('Tata'),
            $this->resolver->getSolution('bar')
        );
        $this->assertSame(
            $this->resolver->resolve('Toto'),
            $this->resolver->resolve('foo')
        );
        $this->assertSame(
            $this->resolver->getSolution('Toto'),
            $this->resolver->getSolution('foo')
        );
    }
}
