<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Resolver;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;
use Overblog\GraphQLBundle\Resolver\TypeResolver;
use Overblog\GraphQLBundle\Resolver\UnresolvableException;
use Overblog\GraphQLBundle\Resolver\UnsupportedResolverException;
use stdClass;
use function sprintf;

class TypeResolverTest extends AbstractResolverTest
{
    protected function createResolver(): TypeResolver
    {
        return new TypeResolver();
    }

    protected function getResolverSolutionsMapping(): array
    {
        return [
            'Toto' => ['factory' => [[$this, 'createObjectType'], [['name' => 'Toto']]], 'aliases' => ['foo']],
            'Tata' => ['factory' => [[$this, 'createObjectType'], [['name' => 'Tata']]], 'aliases' => ['bar']],
        ];
    }

    public function createObjectType(array $config): ObjectType
    {
        return new ObjectType($config);
    }

    public function testAddNotSupportedSolution(): void
    {
        $this->expectException(UnsupportedResolverException::class);
        $this->expectExceptionMessage(sprintf(
            'Resolver "not-supported" must be "%s" "stdClass" given.',
            Type::class
        ));
        $this->resolver->addSolution('not-supported', new stdClass());
        $this->resolver->getSolution('not-supported');
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

    public function testWrongListOfWrappingType(): void
    {
        $this->expectException(UnresolvableException::class);
        $this->expectExceptionMessage('Malformed ListOf wrapper type "[Tata" expected "]" but got ""a"".');
        $this->resolver->resolve('[Tata');
    }

    public function testResolveWithListOfWrapper(): void
    {
        /** @var WrappingType $type */
        $type = $this->resolver->resolve('[Tata]');

        $this->assertInstanceOf(ListOfType::class, $type);
        $this->assertSame('Tata', $type->getWrappedType()->name);
    }

    public function testResolveWithNonNullWrapper(): void
    {
        /** @var WrappingType $type */
        $type = $this->resolver->resolve('Toto!');

        $this->assertInstanceOf(NonNull::class, $type);
        $this->assertSame('Toto', $type->getWrappedType()->name);
    }

    public function testResolveWithNonNullListOfWrapper(): void
    {
        /** @var NonNull $type */
        $type = $this->resolver->resolve('[Toto]!');

        $this->assertInstanceOf(NonNull::class, $type);
        $this->assertInstanceOf(ListOfType::class, $type->getWrappedType());
        $this->assertSame('Toto', $type->getWrappedType()->getWrappedType()->name);
    }

    public function testResolveWitListOfNonNullWrapper(): void
    {
        /** @var ListOfType $type */
        $type = $this->resolver->resolve('[Toto!]');

        $this->assertInstanceOf(ListOfType::class, $type);
        $this->assertInstanceOf(NonNull::class, $type->getWrappedType());
        $this->assertSame('Toto', $type->getWrappedType()->getWrappedType()->name);
    }

    public function testResolveWitNonNullListOfNonNullWrapper(): void
    {
        /** @var NonNull $type */
        $type = $this->resolver->resolve('[Toto!]!');

        $this->assertInstanceOf(NonNull::class, $type);
        $this->assertInstanceOf(ListOfType::class, $type->getWrappedType());
        $this->assertInstanceOf(NonNull::class, $type->getWrappedType()->getWrappedType());
        $this->assertSame('Toto', $type->getWrappedType()->getWrappedType()->getWrappedType()->name);
    }

    public function testResolveWitListOfListOfWrapper(): void
    {
        /** @var ListOfType $type */
        $type = $this->resolver->resolve('[[Toto]]');

        $this->assertInstanceOf(ListOfType::class, $type);
        $this->assertInstanceOf(ListOfType::class, $type->getWrappedType());
        $this->assertSame('Toto', $type->getWrappedType()->getWrappedType()->name);
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
