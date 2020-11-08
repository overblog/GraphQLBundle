<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Resolver;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
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
