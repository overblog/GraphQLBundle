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

final class DocBlockTypeGuesserTest extends TestCase
{
    protected array $reflectors = [
        ReflectionProperty::class => 'var',
        ReflectionMethod::class => 'return',
    ];

    /**
     * @dataProvider guessSuccessDataProvider
     */
    public function testGuessSuccess(string $docType, string $gqlType, ?ClassesTypesMap $map, ?string $reflectorClass): void
    {
        $docBlockGuesser = new DocBlockTypeGuesser($map ?: new ClassesTypesMap());
        $this->assertEquals(
            $gqlType,
            $docBlockGuesser->guessType(
                new ReflectionClass(__CLASS__),
                $this->getMockedReflector($docType, $reflectorClass ?? ReflectionProperty::class)
            )
        );
    }

    public function guessSuccessDataProvider(): iterable
    {
        foreach ($this->reflectors as $reflectorClass => $tag) {
            yield ['string', 'String!', null, $reflectorClass];
            yield ['?string', 'String', null, $reflectorClass];
            yield ['string|null', 'String', null, $reflectorClass];
            yield ['string[]', '[String!]!', null, $reflectorClass];
            yield ['array<string>', '[String!]!', null, $reflectorClass];
            yield ['array<string>|null', '[String!]', null, $reflectorClass];
            yield ['array<string|null>|null', '[String]', null, $reflectorClass];
            yield ['int', 'Int!', null, $reflectorClass];
            yield ['integer', 'Int!', null, $reflectorClass];
            yield ['boolean', 'Boolean!', null, $reflectorClass];
            yield ['bool', 'Boolean!', null, $reflectorClass];
            yield ['float', 'Float!', null, $reflectorClass];
            yield ['double', 'Float!', null, $reflectorClass];
            yield ['iterable<string>', '[String!]!', null, $reflectorClass];
        }

        $map = new ClassesTypesMap();
        $map->addClassType('GQLType1', 'Fake\Class1', 'object');
        $map->addClassType('GQLType2', 'Fake\Class2', 'object');
        $map->addClassType('Foo', ClassesTypesMap::class, 'object');

        yield ['\Fake\Class1[]', '[GQLType1!]!', $map, null];
        yield ['ClassesTypesMap|null', 'Foo', $map, null];
    }

    /**
     * @dataProvider guessErrorDataProvider
     */
    public function testGuessError(string $docType, string $reflectorClass, string $match, bool $canParsingFailed = false): void
    {
        $docBlockGuesser = new DocBlockTypeGuesser(new ClassesTypesMap());
        try {
            $docBlockGuesser->guessType(new ReflectionClass(__CLASS__), $this->getMockedReflector($docType, $reflectorClass));
            $this->fail(sprintf('The @var "%s" should resolve to GraphQL type "%s"', $docType, $match));
        } catch (Exception $e) {
            $this->assertInstanceOf(TypeGuessingException::class, $e);
            if ($canParsingFailed) {
                $this->assertThat($e->getMessage(), $this->logicalOr(
                    $this->equalTo('Doc Block parsing failed with'),
                    $this->equalTo($e->getMessage())
                ));
            } else {
                $this->assertStringContainsString($match, $e->getMessage());
            }
        }
    }

    public function guessErrorDataProvider(): iterable
    {
        foreach ($this->reflectors as $reflectorClass => $tag) {
            yield ['int|float', $reflectorClass, 'Tag @'.$tag.' found, but composite types are only allowed with null'];
            yield ['array<int|float>', $reflectorClass, 'Tag @'.$tag.' found, but composite types in array or iterable are only allowed with null'];
            yield ['UnknownClass', $reflectorClass, 'Tag @'.$tag.' found, but target object "Overblog\GraphQLBundle\Tests\Config\Parser\UnknownClass" is not a GraphQL Type class'];
            yield ['object', $reflectorClass, 'Tag @'.$tag.' found, but type "object" is too generic'];
            yield ['mixed[]', $reflectorClass, 'Tag @'.$tag.' found, but the array values cannot be mixed type'];
            yield ['array<mixed>', $reflectorClass, 'Tag @'.$tag.' found, but the array values cannot be mixed type'];
            yield ['', $reflectorClass, 'No @'.$tag.' tag found in doc block or tag has no type', true];   // phpDocumentor/ReflectionDocBlock
            yield ['[]', $reflectorClass, 'No @'.$tag.' tag found in doc block or tag has no type', true]; // phpDocumentor/ReflectionDocBlock
        }
    }

    public function testMissingDocBlock(): void
    {
        $docBlockGuesser = new DocBlockTypeGuesser(new ClassesTypesMap());
        $mock = $this->createMock(ReflectionProperty::class);
        $mock->method('getDocComment')->willReturn(false);

        try {
            $docBlockGuesser->guessType(new ReflectionClass(__CLASS__), $mock);
        } catch (Exception $e) {
            $this->assertInstanceOf(TypeGuessingException::class, $e);
            $this->assertEquals('Doc Block not found', $e->getMessage());
        }
    }

    /**
     * @return ReflectionProperty|ReflectionMethod
     */
    private function getMockedReflector(string $type, string $className = ReflectionProperty::class)
    {
        // @phpstan-ignore-next-line
        $mock = $this->createMock($className);
        $mock->method('getDocComment')
             ->willReturn(sprintf('/** @%s %s **/', $this->reflectors[$className], $type));

        /** @var ReflectionProperty|ReflectionMethod $mock */
        return $mock;
    }
}
