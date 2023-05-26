<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\EventListener;

use Closure;
use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use InvalidArgumentException;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\ArgumentFactory;
use Overblog\GraphQLBundle\Definition\Type\CustomScalarType;
use Overblog\GraphQLBundle\EventListener\TypeDecoratorListener;
use Overblog\GraphQLBundle\Resolver\ResolverMap;
use Overblog\GraphQLBundle\Resolver\ResolverMapInterface;
use PHPUnit\Framework\TestCase;
use Traversable;

use function substr;

final class TypeDecoratorListenerTest extends TestCase
{
    /**
     * @param string $fieldName
     * @param bool   $strict
     *
     * @dataProvider specialTypeFieldProvider
     */
    public function testSpecialField($fieldName, ObjectType|UnionType|InterfaceType|CustomScalarType $typeWithSpecialField, callable $fieldValueRetriever = null, $strict = true): void
    {
        if (null === $fieldValueRetriever) {
            $fieldValueRetriever = fn (ObjectType|UnionType|InterfaceType|CustomScalarType $type, $fieldName) => $type->config[$fieldName];
        }
        $expected = static function (): void {
        };
        $realFieldName = substr($fieldName, 2);

        $this->decorate(
            [$typeWithSpecialField->name => $typeWithSpecialField],
            [$typeWithSpecialField->name => [$fieldName => $expected]]
        );

        $actual = $fieldValueRetriever($typeWithSpecialField, $realFieldName);

        if ($strict) {
            $this->assertSame($expected, $actual);
        } else {
            $this->assertNotNull($actual);
            $this->assertInstanceOf(Closure::class, $actual);
        }
    }

    public function testObjectTypeFieldDecoration(): ObjectType
    {
        $objectType = new ObjectType([
            'name' => 'Foo',
            'fields' => function (): iterable {
                yield 'bar' => ['type' => Type::string()];
                yield 'baz' => ['type' => Type::string()];
                yield 'toto' => ['type' => Type::boolean(), 'resolve' => null];
            },
        ]);
        $barResolver = static fn () => 'bar';
        $bazResolver = static fn () => 'baz';

        $this->decorate(
            [$objectType->name => $objectType],
            [$objectType->name => ['bar' => $barResolver, 'baz' => $bazResolver]]
        );
        $fields = is_callable($objectType->config['fields']) ? $objectType->config['fields']() : $objectType->config['fields'];
        $fields = $fields instanceof Traversable ? iterator_to_array($fields) : (array) $fields;

        foreach (['bar', 'baz'] as $fieldName) {
            $this->assertArrayHasKey($fieldName, $fields);
            $this->assertInstanceOf(Closure::class, $fields[$fieldName]['resolve']);
            $this->assertSame($fieldName, $fields[$fieldName]['resolve']());
        }

        $this->assertNull($fields['toto']['resolve']);

        return $objectType;
    }

    public function testWrappedResolver(): void
    {
        $objectType = new ObjectType([
            'name' => 'Foo',
            'fields' => function () {
                return [
                    'bar' => ['type' => Type::string()],
                ];
            },
        ]);

        $this->decorate(
            [$objectType->name => $objectType],
            [
                $objectType->name => [
                    'bar' => fn ($value, $args) => $args,
                ],
            ]
        );
        $expected = ['foo' => 'baz'];
        $resolveFn = $objectType->getField('bar')->resolveFn;
        /** @var Argument $args */
        $args = $resolveFn(null, $expected, [], $this->createMock(ResolveInfo::class));

        $this->assertInstanceOf(Argument::class, $args);
        $this->assertSame($expected, $args->getArrayCopy());
    }

    public function testEnumTypeValuesDecoration(): void
    {
        $enumType = new EnumType([
            'name' => 'Foo',
            'values' => [
                'BAR' => ['name' => 'BAR', 'value' => 'BAR'],
                'BAZ' => ['name' => 'BAZ', 'value' => 'BAZ'],
                'TOTO' => ['name' => 'TOTO', 'value' => 'TOTO'],
            ],
        ]);

        $this->decorate(
            [$enumType->name => $enumType],
            [$enumType->name => ['BAR' => 1, 'BAZ' => 2]]
        );

        $this->assertSame(
            [
                'BAR' => ['name' => 'BAR', 'value' => 1],
                'BAZ' => ['name' => 'BAZ', 'value' => 2],
                'TOTO' => ['name' => 'TOTO', 'value' => 'TOTO'],
            ],
            $enumType->config['values']
        );
    }

    public function testEnumTypeLazyValuesDecoration(): void
    {
        $enumType = new EnumType([
            'name' => 'Foo',
            'values' => function (): iterable {
                yield 'BAR' => ['name' => 'BAR', 'value' => 'BAR'];
                yield 'BAZ' => ['name' => 'BAZ', 'value' => 'BAZ'];
                yield 'TOTO' => ['name' => 'TOTO', 'value' => 'TOTO'];
            },
        ]);

        $this->decorate(
            [$enumType->name => $enumType],
            [$enumType->name => ['BAR' => 1, 'BAZ' => 2]]
        );

        $values = is_callable($enumType->config['values']) ? $enumType->config['values']() : $enumType->config['values'];
        $values = $values instanceof Traversable ? iterator_to_array($values) : (array) $values;

        $this->assertSame(
            [
                'BAR' => ['name' => 'BAR', 'value' => 1],
                'BAZ' => ['name' => 'BAZ', 'value' => 2],
                'TOTO' => ['name' => 'TOTO', 'value' => 'TOTO'],
            ],
            $values
        );
    }

    public function testEnumTypeUnknownField(): void
    {
        $enumType = new EnumType([
            'name' => 'Foo',
            'values' => [
                'BAR' => ['name' => 'BAR', 'value' => 'BAR'],
            ],
        ]);
        $this->assertDecorateException(
            [$enumType->name => $enumType],
            [$enumType->name => ['BAZ' => 1]],
            InvalidArgumentException::class,
            '"Foo".{"BAZ"} defined in resolverMap, was defined in resolvers, but enum is not in schema.'
        );
    }

    public function testUnionTypeUnknownField(): void
    {
        $unionType = new UnionType(['name' => 'Foo', 'types' => []]);
        $this->assertDecorateException(
            [$unionType->name => $unionType],
            [
                $unionType->name => [
                    'baz' => function (): void {
                    },
                ],
            ],
            InvalidArgumentException::class,
            '"Foo".{"baz"} defined in resolverMap, but only "Overblog\GraphQLBundle\Resolver\ResolverMapInterface::RESOLVE_TYPE" is allowed.'
        );
    }

    public function testInterfaceTypeUnknownField(): void
    {
        $interfaceType = new InterfaceType(['name' => 'Foo', 'fields' => []]);
        $this->assertDecorateException(
            [$interfaceType->name => $interfaceType],
            [
                $interfaceType->name => [
                    'baz' => function (): void {
                    },
                ],
            ],
            InvalidArgumentException::class,
            '"Foo".{"baz"} defined in resolverMap, but only "Overblog\GraphQLBundle\Resolver\ResolverMapInterface::RESOLVE_TYPE" is allowed.'
        );
    }

    public function testCustomScalarTypeUnknownField(): void
    {
        $customScalarType = new CustomScalarType(['name' => 'Foo', 'scalarType' => Type::string(), 'serialize' => fn (mixed $input): mixed => '']);
        $this->assertDecorateException(
            [$customScalarType->name => $customScalarType],
            [
                $customScalarType->name => [
                    'baz' => function (): void {
                    },
                ],
            ],
            InvalidArgumentException::class,
            '"Foo".{"baz"} defined in resolverMap, but only "Overblog\GraphQLBundle\Resolver\ResolverMapInterface::{SCALAR_TYPE, SERIALIZE, PARSE_VALUE, PARSE_LITERAL}" is allowed.'
        );
    }

    public function testObjectTypeUnknownField(): void
    {
        $objectType = new ObjectType([
            'name' => 'Foo',
            'fields' => [
                'bar' => ['type' => Type::string()],
            ],
        ]);
        $this->assertDecorateException(
            [$objectType->name => $objectType],
            [
                $objectType->name => [
                    'baz' => function (): void {
                    },
                ],
            ],
            InvalidArgumentException::class,
            '"Foo".{"baz"} defined in resolverMap, but not in schema.'
        );
    }

    public function testUnSupportedTypeDefineInResolverMapShouldThrowAnException(): void
    {
        $this->assertDecorateException(
            ['myType' => new InputObjectType(['name' => 'myType', 'fields' => []])],
            [
                'myType' => [
                    'foo' => null,
                    'bar' => null,
                ],
            ],
            InvalidArgumentException::class,
            '"myType".{"foo", "bar"} defined in resolverMap, but type is not managed by TypeDecorator.'
        );
    }

    public function specialTypeFieldProvider(): array
    {
        $objectWithResolveField = new ObjectType(['name' => 'Bar', 'fields' => [], 'resolveField' => fn () => '']);

        return [
            // isTypeOf
            [ResolverMapInterface::IS_TYPEOF, new ObjectType(['name' => 'Foo', 'fields' => [], 'isTypeOf' => null])],
            // resolveField
            [
                ResolverMapInterface::RESOLVE_FIELD,
                $objectWithResolveField,
                fn (ObjectType $type) => $type->resolveFieldFn,
                false,
            ],
            [ResolverMapInterface::RESOLVE_FIELD, $objectWithResolveField, null, false],
            // resolveType
            [ResolverMapInterface::RESOLVE_TYPE, new UnionType(['name' => 'Baz', 'resolveType' => fn () => '', 'types' => []])],
            [ResolverMapInterface::RESOLVE_TYPE, new InterfaceType(['name' => 'Baz', 'fields' => [], 'resolveType' => fn (mixed $objectValue, mixed $context, ResolveInfo $resolveInfo): string => ''])],
            // custom scalar
            [ResolverMapInterface::SERIALIZE, new CustomScalarType(['name' => 'Custom', 'scalarType' => Type::string(), 'serialize' => fn (mixed $input): mixed => ''])],
            [ResolverMapInterface::PARSE_VALUE, new CustomScalarType(['name' => 'Custom', 'scalarType' => Type::string(), 'serialize' => fn (mixed $input): mixed => '', 'parseValue' => fn (mixed $input): mixed => ''])],
            [ResolverMapInterface::PARSE_LITERAL, new CustomScalarType(['name' => 'Custom', 'scalarType' => Type::string(), 'serialize' => fn (mixed $input): mixed => '', 'parseLiteral' => fn (Node $a, array|null $b): mixed => ''])],
            [ResolverMapInterface::SCALAR_TYPE, new CustomScalarType(['name' => 'Custom', 'scalarType' => Type::string(), 'serialize' => fn (mixed $input): mixed => ''])],
        ];
    }

    private function assertDecorateException(array $types, array $map, string $exception = null, string $exceptionMessage = null): void
    {
        if ($exception) {
            $this->expectException($exception); // @phpstan-ignore-line
        }
        if ($exceptionMessage) {
            $this->expectExceptionMessage($exceptionMessage);
        }

        $this->decorate($types, $map);
    }

    private function decorate(array $types, array $map): void
    {
        $typeDecoratorListener = new TypeDecoratorListener(new ArgumentFactory(Argument::class));

        foreach ($types as $type) {
            $typeDecoratorListener->decorateType($type, $this->createResolverMapMock($map));
        }
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ResolverMap
     */
    private function createResolverMapMock(array $map = [])
    {
        $resolverMap = $this->getMockBuilder(ResolverMap::class)->setMethods(['map'])->getMock();
        $resolverMap->expects($this->any())->method('map')->willReturn($map);

        return $resolverMap;
    }
}
