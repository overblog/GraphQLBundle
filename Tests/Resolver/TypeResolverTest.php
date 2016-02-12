<?php

namespace Tests\Overblog\GraphBundle\Resolver;

use Overblog\GraphBundle\Resolver\TypeResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use GraphQL\Type\Definition\ObjectType;

class TypeResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ContainerBuilder */
    private static $container;

    /** @var  TypeResolver */
    private static $typeResolver;

    public static function setUpBeforeClass()
    {
        $container = new ContainerBuilder();

        $mapping = [
            'Toto' => ['id' => 'overblog_graph.definition.custom_toto_type', 'alias' => 'Toto'],
            'Tata' => ['id' => 'overblog_graph.definition.custom_tata_type', 'alias' => 'Tata'],
        ];

        $container->setParameter('overblog_graph.types_mapping', $mapping);

        foreach($mapping as $alias => $options) {
            $container->setDefinition($options['id'], new Definition('GraphQL\Type\Definition\ObjectType'))
                ->setArguments([
                    ['name' => $alias]
                ]);
        }

        self::$container = $container;
        self::$typeResolver = new TypeResolver(self::$container);
    }

    public function testResolveKnownType()
    {
        $type = self::$typeResolver->resolve('Toto');

        $this->assertInstanceOf('GraphQL\Type\Definition\ObjectType', $type);
        $this->assertEquals('Toto', $type->name);
    }

    /**
     * @expectedException \Overblog\GraphBundle\Resolver\UnresolvableException
     */
    public function testResolveUnknownType()
    {
        self::$typeResolver->resolve('Fake');
    }

    public function testResolveWithListOfWrapper()
    {
        /** @var \GraphQL\Type\Definition\WrappingType $type */
        $type = self::$typeResolver->resolve('[Tata]');

        $this->assertInstanceOf('GraphQL\Type\Definition\ListOfType', $type);
        $this->assertEquals('Tata', $type->getWrappedType());
    }

    public function testResolveWithNonNullWrapper()
    {
        /** @var \GraphQL\Type\Definition\WrappingType $type */
        $type = self::$typeResolver->resolve('Toto!');

        $this->assertInstanceOf('GraphQL\Type\Definition\NonNull', $type);
        $this->assertEquals('Toto', $type->getWrappedType());
    }

    public function testResolveWithNonNullListOfWrapper()
    {
        /** @var \GraphQL\Type\Definition\WrappingType $type */
        $type = self::$typeResolver->resolve('[Toto]!');

        $this->assertInstanceOf('GraphQL\Type\Definition\NonNull', $type);
        $this->assertInstanceOf('GraphQL\Type\Definition\ListOfType', $type->getWrappedType());
        $this->assertEquals('Toto', $type->getWrappedType()->getWrappedType());
    }

    public function testResolveWitListOfNonNullWrapper()
    {
        /** @var \GraphQL\Type\Definition\WrappingType $type */
        $type = self::$typeResolver->resolve('[Toto!]');

        $this->assertInstanceOf('GraphQL\Type\Definition\ListOfType', $type);
        $this->assertInstanceOf('GraphQL\Type\Definition\NonNull', $type->getWrappedType());
        $this->assertEquals('Toto', $type->getWrappedType()->getWrappedType());
    }

    public function testResolveWitNonNullListOfNonNullWrapper()
    {
        /** @var \GraphQL\Type\Definition\WrappingType $type */
        $type = self::$typeResolver->resolve('[Toto!]!');

        $this->assertInstanceOf('GraphQL\Type\Definition\NonNull', $type);
        $this->assertInstanceOf('GraphQL\Type\Definition\ListOfType', $type->getWrappedType());
        $this->assertInstanceOf('GraphQL\Type\Definition\NonNull', $type->getWrappedType()->getWrappedType());
        $this->assertEquals('Toto', $type->getWrappedType()->getWrappedType()->getWrappedType());
    }

    public function testResolveWitListOfListOfWrapper()
    {
        /** @var \GraphQL\Type\Definition\WrappingType $type */
        $type = self::$typeResolver->resolve('[[Toto]]');

        $this->assertInstanceOf('GraphQL\Type\Definition\ListOfType', $type);
        $this->assertInstanceOf('GraphQL\Type\Definition\ListOfType', $type->getWrappedType());
        $this->assertEquals('Toto', $type->getWrappedType()->getWrappedType());
    }
}
