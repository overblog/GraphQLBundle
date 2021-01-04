<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser;

use Exception;
use Overblog\GraphQLBundle\Config\Parser\MetadataParser\ClassesTypesMap;
use Overblog\GraphQLBundle\Config\Parser\MetadataParser\TypeGuesser\DocBlockTypeGuesser;
use Overblog\GraphQLBundle\Config\Parser\MetadataParser\TypeGuesser\TypeGuessingException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use function sprintf;

class DocBlockTypeGuesserTest extends TestCase
{
    protected $reflectors = [
        ReflectionProperty::class => 'var',
        ReflectionMethod::class => 'return',
    ];

    public function testGuess(): void
    {
        foreach ($this->reflectors as $reflectorClass => $tag) {
            $this->doTest('string', 'String!', null, $reflectorClass);
            $this->doTest('?string', 'String', null, $reflectorClass);
            $this->doTest('string|null', 'String', null, $reflectorClass);
            $this->doTest('string[]', '[String!]!', null, $reflectorClass);
            $this->doTest('array<string>', '[String!]!', null, $reflectorClass);
            $this->doTest('array<string>|null', '[String!]', null, $reflectorClass);
            $this->doTest('array<string|null>|null', '[String]', null, $reflectorClass);
            $this->doTest('int', 'Int!', null, $reflectorClass);
            $this->doTest('integer', 'Int!', null, $reflectorClass);
            $this->doTest('boolean', 'Boolean!', null, $reflectorClass);
            $this->doTest('bool', 'Boolean!', null, $reflectorClass);
            $this->doTest('float', 'Float!', null, $reflectorClass);
            $this->doTest('double', 'Float!', null, $reflectorClass);
            $this->doTest('iterable<string>', '[String!]!', null, $reflectorClass);

            $this->doTestError('int|float', $reflectorClass, 'Tag @'.$tag.' found, but composite types are only allowed with null');
            $this->doTestError('array<int|float>', $reflectorClass, 'Tag @'.$tag.' found, but composite types in array or iterable are only allowed with null');
            $this->doTestError('UnknownClass', $reflectorClass, 'Tag @'.$tag.' found, but target object "Overblog\GraphQLBundle\Tests\Config\Parser\UnknownClass" is not a GraphQL Type class');
            $this->doTestError('object', $reflectorClass, 'Tag @'.$tag.' found, but type "object" is too generic');
            $this->doTestError('mixed[]', $reflectorClass, 'Tag @'.$tag.' found, but the array values cannot be mixed type');
            $this->doTestError('array<mixed>', $reflectorClass, 'Tag @'.$tag.' found, but the array values cannot be mixed type');
            $this->doTestError('', $reflectorClass, 'No @'.$tag.' tag found in doc block or tag has no type');
            $this->doTestError('[]', $reflectorClass, 'Doc Block parsing failed');

            $map = new ClassesTypesMap();
            $map->addClassType('GQLType1', 'Fake\Class1', 'object');
            $map->addClassType('GQLType2', 'Fake\Class2', 'object');
            $map->addClassType('Foo', ClassesTypesMap::class, 'object');

            $this->doTest('\Fake\Class1[]', '[GQLType1!]!', $map);
            $this->doTest('ClassesTypesMap|null', 'Foo', $map);
        }
    }

    protected function doTest(string $docType, string $gqlType, ClassesTypesMap $map = null, string $reflectorClass = ReflectionProperty::class)
    {
        $docBlockGuesser = new DocBlockTypeGuesser($map ?: new ClassesTypesMap());
        $this->assertEquals($gqlType, $docBlockGuesser->guessType(new ReflectionClass(__CLASS__), $this->getMockedReflector($docType, $reflectorClass)));
    }

    protected function doTestError(string $docType, string $reflectorClass, string $match)
    {
        $docBlockGuesser = new DocBlockTypeGuesser(new ClassesTypesMap());
        try {
            $docBlockGuesser->guessType(new ReflectionClass(__CLASS__), $this->getMockedReflector($docType, $reflectorClass));
            $this->fail(sprintf('The @var "%s" should resolve to GraphQL type "%s"', $docType, $match));
        } catch (Exception $e) {
            $this->assertInstanceOf(TypeGuessingException::class, $e);
            $this->assertStringContainsString($match, $e->getMessage());
        }
    }

    protected function getMockedReflector(string $type, string $className = ReflectionProperty::class)
    {
        $mock = $this->createMock($className);
        $mock->method('getDocComment')
             ->willReturn(sprintf('/** @%s %s **/', $this->reflectors[$className], $type));

        return $mock;
    }
}
