<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Resolver;

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
            'Toto' => ['solution' => new ObjectType(['name' => 'Toto'])],
            'Tata' => ['solution' => new ObjectType(['name' => 'Tata'])],
        ];
    }

    public function testResolveKnownType()
    {
        $type = $this->resolver->resolve('Toto');

        $this->assertInstanceOf('GraphQL\Type\Definition\ObjectType', $type);
        $this->assertEquals('Toto', $type->name);
    }

    /**
     * @expectedException \Overblog\GraphQLBundle\Resolver\UnresolvableException
     */
    public function testResolveUnknownType()
    {
        $this->resolver->resolve('Fake');
    }

    public function testResolveWithListOfWrapper()
    {
        /** @var \GraphQL\Type\Definition\WrappingType $type */
        $type = $this->resolver->resolve('[Tata]');

        $this->assertInstanceOf('GraphQL\Type\Definition\ListOfType', $type);
        $this->assertEquals('Tata', $type->getWrappedType());
    }

    public function testResolveWithNonNullWrapper()
    {
        /** @var \GraphQL\Type\Definition\WrappingType $type */
        $type = $this->resolver->resolve('Toto!');

        $this->assertInstanceOf('GraphQL\Type\Definition\NonNull', $type);
        $this->assertEquals('Toto', $type->getWrappedType());
    }

    public function testResolveWithNonNullListOfWrapper()
    {
        /** @var \GraphQL\Type\Definition\WrappingType $type */
        $type = $this->resolver->resolve('[Toto]!');

        $this->assertInstanceOf('GraphQL\Type\Definition\NonNull', $type);
        $this->assertInstanceOf('GraphQL\Type\Definition\ListOfType', $type->getWrappedType());
        $this->assertEquals('Toto', $type->getWrappedType()->getWrappedType());
    }

    public function testResolveWitListOfNonNullWrapper()
    {
        /** @var \GraphQL\Type\Definition\WrappingType $type */
        $type = $this->resolver->resolve('[Toto!]');

        $this->assertInstanceOf('GraphQL\Type\Definition\ListOfType', $type);
        $this->assertInstanceOf('GraphQL\Type\Definition\NonNull', $type->getWrappedType());
        $this->assertEquals('Toto', $type->getWrappedType()->getWrappedType());
    }

    public function testResolveWitNonNullListOfNonNullWrapper()
    {
        /** @var \GraphQL\Type\Definition\WrappingType $type */
        $type = $this->resolver->resolve('[Toto!]!');

        $this->assertInstanceOf('GraphQL\Type\Definition\NonNull', $type);
        $this->assertInstanceOf('GraphQL\Type\Definition\ListOfType', $type->getWrappedType());
        $this->assertInstanceOf('GraphQL\Type\Definition\NonNull', $type->getWrappedType()->getWrappedType());
        $this->assertEquals('Toto', $type->getWrappedType()->getWrappedType()->getWrappedType());
    }

    public function testResolveWitListOfListOfWrapper()
    {
        /** @var \GraphQL\Type\Definition\WrappingType $type */
        $type = $this->resolver->resolve('[[Toto]]');

        $this->assertInstanceOf('GraphQL\Type\Definition\ListOfType', $type);
        $this->assertInstanceOf('GraphQL\Type\Definition\ListOfType', $type->getWrappedType());
        $this->assertEquals('Toto', $type->getWrappedType()->getWrappedType());
    }
}
