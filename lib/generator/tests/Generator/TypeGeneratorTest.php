<?php declare(strict_types=1);

/*
 * This file is part of the OverblogGraphQLPhpGenerator package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLGenerator\Tests\Generator;

use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\FloatType;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;

class TypeGeneratorTest extends AbstractTypeGeneratorTest
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Skeleton dir "fake" not found.
     */
    public function testWrongSetSkeletonDirs(): void
    {
        $this->typeGenerator->setSkeletonDirs(['fake']);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Skeleton dir must be string or object implementing __toString, "array" given.
     */
    public function testWrongAddSkeletonDir(): void
    {
        $this->typeGenerator->addSkeletonDir([]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Skeleton dirs must be array or object implementing \Traversable interface, "object" given.
     */
    public function testWrongObjectSetSkeletonDir(): void
    {
        $this->typeGenerator->setSkeletonDirs(new \stdClass());
    }


    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp  /Skeleton "fake" could not be found in .*\/skeleton./
     */
    public function testWrongGetSkeletonDirs(): void
    {
        $this->typeGenerator->getSkeletonContent('fake');
    }

    public function testTypeAlias2String(): void
    {
        $this->generateClasses($this->getConfigs());

        /** @var ObjectType $type */
        $type = $this->getType('T');

        $this->assertInstanceOf(StringType::class, $type->getField('string')->getType());
        $this->assertInstanceOf(IntType::class, $type->getField('int')->getType());
        $this->assertInstanceOf(IDType::class, $type->getField('id')->getType());
        $this->assertInstanceOf(FloatType::class, $type->getField('float')->getType());
        $this->assertInstanceOf(BooleanType::class, $type->getField('boolean')->getType());

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
    public function testTypeAlias2StringInvalidListOf(): void
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

    public function testAddTraitAndClearTraits(): void
    {
        $trait = FooTrait::class;
        $interface = FooInterface::class;
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
        $this->assertFalse(\method_exists($type, 'bar'));
    }

    public function testCallbackEntryDoesNotTreatObject(): void
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
        $this->assertInstanceOf(\Closure::class, $resolveFn);
        $this->assertEquals(['result' => 1], $resolveFn());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Generator [Overblog\GraphQLGenerator\Generator\TypeGenerator::generateFake] for placeholder "fake" is not callable.
     */
    public function testProcessInvalidPlaceHoldersReplacements(): void
    {
        $this->typeGenerator->setSkeletonDirs(__DIR__.'/../Resources/Skeleton');

        $this->generateClasses($this->getConfigs());
    }

    public function testTypeSingletonCantBeClone(): void
    {
        $this->generateClasses($this->getConfigs());

        /** @var ObjectType $type */
        $type = $this->getType('T');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('You can not clone a singleton.');

        $t = clone $type;
    }

    public function testTypeSingletonCanBeInstantiatedOnlyOnce(): void
    {
        $this->generateClasses($this->getConfigs());

        /** @var ObjectType $type */
        $type = $this->getType('T');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('You can not create more than one copy of a singleton.');

        $class = \get_class($type);
        $t = new $class();
    }

    private function getConfigs(): array
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
