<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Resolver;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use Overblog\GraphQLBundle\Resolver\TypeResolver;

class TypeResolverTest extends AbstractResolverTest
{
    protected function createResolver()
    {
        return new TypeResolver();
    }

    protected function getResolverSolutionsMapping()
    {
        return [
            'Toto' => ['factory' => [[$this, 'createObjectType'], [['name' => 'Toto']]], 'aliases' => ['foo']],
            'Tata' => ['factory' => [[$this, 'createObjectType'], [['name' => 'Tata']]], 'aliases' => ['bar']],
        ];
    }

    public function createObjectType(array $config)
    {
        return new ObjectType($config);
    }

    /**
     * @expectedException \Overblog\GraphQLBundle\Resolver\UnsupportedResolverException
     * @expectedExceptionMessage Resolver "not-supported" must be "GraphQL\Type\Definition\Type" "stdClass" given.
     */
    public function testAddNotSupportedSolution(): void
    {
        $this->resolver->addSolution('not-supported', new \stdClass());
        $this->resolver->getSolution('not-supported');
    }

    public function testResolveKnownType(): void
    {
        $type = $this->resolver->resolve('Toto');

        $this->assertInstanceOf(ObjectType::class, $type);
        $this->assertSame('Toto', $type->name);
    }

    /**
     * @expectedException \Overblog\GraphQLBundle\Resolver\UnresolvableException
     */
    public function testResolveUnknownType(): void
    {
        $this->resolver->resolve('Fake');
    }

    /**
     * @expectedException \Overblog\GraphQLBundle\Resolver\UnresolvableException
     * @expectedExceptionMessage Malformed ListOf wrapper type "[Tata" expected "]" but got ""a"".
     */
    public function testWrongListOfWrappingType(): void
    {
        $this->resolver->resolve('[Tata');
    }

    public function testResolveWithListOfWrapper(): void
    {
        /** @var \GraphQL\Type\Definition\WrappingType $type */
        $type = $this->resolver->resolve('[Tata]');

        $this->assertInstanceOf(ListOfType::class, $type);
        $this->assertSame('Tata', $type->getWrappedType()->name);
    }

    public function testResolveWithNonNullWrapper(): void
    {
        /** @var \GraphQL\Type\Definition\WrappingType $type */
        $type = $this->resolver->resolve('Toto!');

        $this->assertInstanceOf(NonNull::class, $type);
        $this->assertSame('Toto', $type->getWrappedType()->name);
    }

    public function testResolveWithNonNullListOfWrapper(): void
    {
        /** @var \GraphQL\Type\Definition\WrappingType $type */
        $type = $this->resolver->resolve('[Toto]!');

        $this->assertInstanceOf(NonNull::class, $type);
        $this->assertInstanceOf(ListOfType::class, $type->getWrappedType());
        $this->assertSame('Toto', $type->getWrappedType()->getWrappedType()->name);
    }

    public function testResolveWitListOfNonNullWrapper(): void
    {
        /** @var \GraphQL\Type\Definition\WrappingType $type */
        $type = $this->resolver->resolve('[Toto!]');

        $this->assertInstanceOf(ListOfType::class, $type);
        $this->assertInstanceOf(NonNull::class, $type->getWrappedType());
        $this->assertSame('Toto', $type->getWrappedType()->getWrappedType()->name);
    }

    public function testResolveWitNonNullListOfNonNullWrapper(): void
    {
        /** @var \GraphQL\Type\Definition\WrappingType $type */
        $type = $this->resolver->resolve('[Toto!]!');

        $this->assertInstanceOf(NonNull::class, $type);
        $this->assertInstanceOf(ListOfType::class, $type->getWrappedType());
        $this->assertInstanceOf(NonNull::class, $type->getWrappedType()->getWrappedType());
        $this->assertSame('Toto', $type->getWrappedType()->getWrappedType()->getWrappedType()->name);
    }

    public function testResolveWitListOfListOfWrapper(): void
    {
        /** @var \GraphQL\Type\Definition\WrappingType $type */
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
