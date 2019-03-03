<?php

/*
 * This file is part of the OverblogGraphQLPhpGenerator package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLGenerator\Tests\Generator;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class TypeGeneratorTest extends AbstractTypeGeneratorTest
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Skeleton dir "fake" not found.
     */
    public function testWrongSetSkeletonDirs()
    {
        $this->typeGenerator->setSkeletonDirs(['fake']);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Skeleton dir must be string or object implementing __toString, "array" given.
     */
    public function testWrongAddSkeletonDir()
    {
        $this->typeGenerator->addSkeletonDir([]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Skeleton dirs must be array or object implementing \Traversable interface, "object" given.
     */
    public function testWrongObjectSetSkeletonDir()
    {
        $this->typeGenerator->setSkeletonDirs(new \stdClass());
    }


    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp  /Skeleton "fake" could not be found in .*\/skeleton./
     */
    public function testWrongGetSkeletonDirs()
    {
        $this->typeGenerator->getSkeletonContent('fake');
    }

    public function testTypeAlias2String()
    {
        $this->generateClasses($this->getConfigs());

        /** @var ObjectType $type */
        $type = $this->getType('T');

        $this->assertInstanceOf('GraphQL\Type\Definition\StringType', $type->getField('string')->getType());
        $this->assertInstanceOf('GraphQL\Type\Definition\IntType', $type->getField('int')->getType());
        $this->assertInstanceOf('GraphQL\Type\Definition\IDType', $type->getField('id')->getType());
        $this->assertInstanceOf('GraphQL\Type\Definition\FloatType', $type->getField('float')->getType());
        $this->assertInstanceOf('GraphQL\Type\Definition\BooleanType', $type->getField('boolean')->getType());

        $this->assertEquals(Type::nonNull(Type::string()), $type->getField('nonNullString')->getType());
        $this->assertEquals(Type::listOf(Type::string()), $type->getField('listOfString')->getType());
        $this->assertEquals(Type::listOf(Type::listOf(Type::string())), $type->getField('listOfListOfString')->getType());
        $this->assertEquals(
            Type::nonNull(
                Type::listOf(
                    Type::nonNull(
                        Type::listOf(
                            Type::nonNull(Type::string())
                        )
                    )
                )
            ),
            $type->getField('listOfListOfStringNonNull')->getType()
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Malformed ListOf wrapper type "[String" expected "]" but got "g".
     */
    public function testTypeAlias2StringInvalidListOf()
    {
        $this->generateClasses([
            'T' => [
                'type' => 'object',
                'config' => [
                    'fields' => [
                        'invalidlistOfString' => ['type' => '[String'],
                    ]
                ],
            ]
        ]);
    }

    public function testAddTraitAndClearTraits()
    {
        $trait = __NAMESPACE__ . '\\FooTrait';
        $interface = __NAMESPACE__ . '\\FooInterface';
        $this->typeGenerator->addTrait($trait)
            ->addImplement($interface);
        $this->generateClasses(['U' => $this->getConfigs()['T']]);

        /** @var FooInterface|ObjectType $type */
        $type = $this->getType('U');

        $this->assertInstanceOf($interface, $type);
        $this->assertEquals('Foo::bar', $type->bar());

        $this->typeGenerator->clearTraits()
            ->clearImplements()
            ->clearUseStatements();
        $this->generateClasses(['V' => $this->getConfigs()['T']]);

        /** @var ObjectType $type */
        $type = $this->getType('V');

        $this->assertNotInstanceOf($interface, $type);
        $this->assertFalse(method_exists($type, 'bar'));
    }

    public function testCallbackEntryDoesNotTreatObject()
    {
        $this->generateClasses([
            'W' => [
                'type' => 'object',

                'config' => [
                    'description' => new \stdClass(),
                    'fields' => [
                        'resolveObject' => ['type' => '[String]', 'resolve' => new \stdClass()],
                        'resolveAnyNotObject' => ['type' => '[String]', 'resolve' => ['result' => 1]],
                    ]
                ],
            ]
        ]);

        /** @var ObjectType $type */
        $type = $this->getType('W');

        $this->assertNull($type->getField('resolveObject')->resolveFn);
        $this->assertNull($type->getField('resolveObject')->description);
        $resolveFn = $type->getField('resolveAnyNotObject')->resolveFn;
        $this->assertInstanceOf('\Closure', $resolveFn);
        $this->assertEquals(['result' => 1], $resolveFn());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Generator [Overblog\GraphQLGenerator\Generator\TypeGenerator::generateFake] for placeholder "fake" is not callable.
     */
    public function testProcessInvalidPlaceHoldersReplacements()
    {
        $this->typeGenerator->setSkeletonDirs(__DIR__.'/../Resources/Skeleton');

        $this->generateClasses($this->getConfigs());
    }

    public function testTypeSingletonCantBeClone()
    {
        $this->generateClasses($this->getConfigs());

        /** @var ObjectType $type */
        $type = $this->getType('T');

        $this->setExpectedException('\DomainException', 'You can not clone a singleton.');

        $t = clone $type;
    }

    public function testTypeSingletonCanBeInstantiatedOnlyOnce()
    {
        $this->generateClasses($this->getConfigs());

        /** @var ObjectType $type */
        $type = $this->getType('T');

        $this->setExpectedException('\DomainException', 'You can not create more than one copy of a singleton.');

        $class = get_class($type);
        $t = new $class();
    }

    private function getConfigs()
    {
        return [
            'T' => [
                'type' => 'object',
                'config' => [
                    'fields' => [
                        'string' => ['type' => 'String'],
                        'int' => ['type' => 'Int'],
                        'id' => ['type' => 'ID'],
                        'float' => ['type' => 'Float'],
                        'boolean' => ['type' => 'Boolean'],
                        'nonNullString' => ['type' => 'String!'],
                        'listOfString' => ['type' => '[String]'],
                        'listOfListOfString' => ['type' => '[[String]]'],
                        'listOfListOfStringNonNull' => ['type' => '[[String!]!]!'],
                    ]
                ],
            ]
        ];
    }
}
