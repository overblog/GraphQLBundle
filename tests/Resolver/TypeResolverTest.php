<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Resolver;

use GraphQL\Type\Definition\ObjectType;
use Overblog\GraphQLBundle\Resolver\TypeResolver;
use Overblog\GraphQLBundle\Resolver\UnresolvableException;

final class TypeResolverTest extends TestAbstractResolver
{
    protected function createResolver(): TypeResolver
    {
        return new TypeResolver();
    }

    protected function getResolverSolutionsMapping(): array
    {
        return [
            'Toto' => ['factory' => fn () => new ObjectType(['name' => 'Toto', 'fields' => []]), 'aliases' => ['foo']],
            'Tata' => ['factory' => fn () => new ObjectType(['name' => 'Tata', 'fields' => []]), 'aliases' => ['bar']],
        ];
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

    public function testGetCurrentSchemaNameDefaultIsNull(): void
    {
        $resolver = new TypeResolver();

        $this->assertNull($resolver->getCurrentSchemaName());
    }

    public function testGetCurrentSchemaNameAfterSet(): void
    {
        $resolver = new TypeResolver();
        $resolver->setCurrentSchemaName('api');

        $this->assertSame('api', $resolver->getCurrentSchemaName());
    }

    public function testGetCurrentSchemaNameCanBeSetToNull(): void
    {
        $resolver = new TypeResolver();
        $resolver->setCurrentSchemaName('api');
        $resolver->setCurrentSchemaName(null);

        $this->assertNull($resolver->getCurrentSchemaName());
    }

    public function testResetClearsSolutions(): void
    {
        $callCount = 0;
        $resolver = new TypeResolver();
        $resolver->addSolution('Foo', function () use (&$callCount): ObjectType {
            ++$callCount;

            return new ObjectType(['name' => 'Foo', 'fields' => []]);
        });

        $first = $resolver->resolve('Foo');
        $this->assertSame(1, $callCount);

        // resolve again without reset — factory not called again
        $resolver->resolve('Foo');
        $this->assertSame(1, $callCount);

        $resolver->reset();

        // After reset factory is called again
        $second = $resolver->resolve('Foo');
        $this->assertGreaterThan(1, $callCount);

        // The returned instances may differ (new ObjectType created each time)
        $this->assertInstanceOf(ObjectType::class, $first);
        $this->assertInstanceOf(ObjectType::class, $second);
    }

    public function testAfterResetCanStillResolve(): void
    {
        $resolver = new TypeResolver();
        $resolver->addSolution('Bar', fn () => new ObjectType(['name' => 'Bar', 'fields' => []]));

        $this->assertTrue($resolver->hasSolution('Bar'));
        $resolver->reset();

        $this->assertTrue($resolver->hasSolution('Bar'), 'hasSolution should still return true after reset');

        $type = $resolver->resolve('Bar');
        $this->assertInstanceOf(ObjectType::class, $type);
        $this->assertSame('Bar', $type->name);
    }

    public function testResetAlsoClearsCache(): void
    {
        $callCount = 0;
        $resolver = new TypeResolver();
        $resolver->addSolution('Baz', function () use (&$callCount): ObjectType {
            ++$callCount;

            return new ObjectType(['name' => 'Baz', 'fields' => []]);
        });

        $resolver->resolve('Baz');
        $this->assertSame(1, $callCount);

        $resolver->reset();

        $resolver->resolve('Baz');
        $this->assertGreaterThan(1, $callCount, 'After reset, the cache is cleared so factory is called again');
    }
}
