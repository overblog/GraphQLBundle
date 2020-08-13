<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Resolver;

use ArrayAccess;
use ArrayObject;
use Closure;
use Overblog\GraphQLBundle\Resolver\ResolverMap;
use Overblog\GraphQLBundle\Resolver\UnresolvableException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use function array_keys;
use function array_merge;
use function get_class;
use function sprintf;

class ResolverMapTest extends TestCase
{
    /**
     * @param array|ArrayAccess $map
     * @param string            $typeName
     * @param string            $fieldName
     * @param Closure|null      $expectedResolver
     *
     * @dataProvider validMapDataProvider
     */
    public function testResolve($map, $typeName, $fieldName, $expectedResolver): void
    {
        $resolverMap = $this->createResolverMapMock($map);
        $resolver = $resolverMap->resolve($typeName, $fieldName);
        $this->assertSame($expectedResolver, $resolver);
    }

    public function testCoveredWithTypeNameNull(): void
    {
        $map = $this->map();
        $resolverMap = $this->createResolverMapMock($map);
        $covered = $resolverMap->covered();
        $this->assertSame(array_keys($map), $covered);
    }

    /**
     * @dataProvider invalidMapDataProvider
     *
     * @param mixed  $invalidMap
     * @param string $invalidType
     */
    public function testInvalidMap($invalidMap, $invalidType): void
    {
        $resolverMap = $this->createResolverMapMock($invalidMap);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            '%s::map() should return an array or an instance of \ArrayAccess and \Traversable but got "%s".',
            get_class($resolverMap),
            $invalidType
        ));
        $resolverMap->resolve('Foo', 'bar');
    }

    public function testUnresolvable(): void
    {
        $resolverMap = $this->createResolverMapMock([
            'Query' => [
                ResolverMap::RESOLVE_FIELD => function (): void {
                },
            ],
        ]);
        $this->expectException(UnresolvableException::class);
        $this->expectExceptionMessage('Field "Foo.bar" could not be resolved.');
        $resolverMap->resolve('Foo', 'bar');
    }

    public function invalidMapDataProvider(): array
    {
        return [
            [null, 'NULL'],
            [false, 'boolean'],
            [true, 'boolean'],
            ['baz', 'string'],
            [new stdClass(), 'stdClass'],
        ];
    }

    public function validMapDataProvider(): array
    {
        $arrayMap = $this->map();
        $objectMap = new ArrayObject($arrayMap);

        $validMap = [];

        foreach ([$arrayMap, $objectMap] as $map) {
            $validMap = array_merge($validMap, [
                [$map, 'Query', ResolverMap::RESOLVE_FIELD, $map['Query'][ResolverMap::RESOLVE_FIELD]],
                [$map, 'Query', 'foo', $map['Query']['foo']],
                [$map, 'Query', 'bar', $map['Query']['bar']],
                [$map, 'Query', 'baz', null],
                [$map, 'FooInterface', ResolverMap::RESOLVE_TYPE, $map['FooInterface'][ResolverMap::RESOLVE_TYPE]],
            ]);
        }

        return $validMap;
    }

    /**
     * @param mixed $map
     *
     * @return ResolverMap|MockObject
     */
    private function createResolverMapMock($map)
    {
        /** @var ResolverMap|MockObject $resolverMap */
        $resolverMap = $this->getMockBuilder(ResolverMap::class)->setMethods(['map'])->getMock();
        $resolverMap->method('map')->willReturn($map);

        return $resolverMap;
    }

    private function map(): array
    {
        return [
            'Query' => [
                ResolverMap::RESOLVE_FIELD => function (): void {
                },
                'foo' => function (): void {
                },
                'bar' => function (): void {
                },
                'baz' => null,
            ],
            'FooInterface' => [
                ResolverMap::RESOLVE_TYPE => function (): void {
                },
            ],
        ];
    }
}
