<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\EventListener;

use Closure;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
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
use function substr;

class TypeDecoratorListenerTest extends TestCase
{
    /**
     * @param string $fieldName
     * @param bool   $strict
     *
     * @dataProvider specialTypeFieldProvider
     */
    public function testSpecialField($fieldName, Type $typeWithSpecialField, callable $fieldValueRetriever = null, $strict = true): void
    {
        if (null === $fieldValueRetriever) {
            $fieldValueRetriever = function (Type $type, $fieldName) {
                return $type->config[$fieldName];
            };
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
            'fields' => function () {
                return [
                    'bar' => ['type' => Type::string()],
                    'baz' => ['type' => Type::string()],
                    'toto' => ['type' => Type::boolean(), 'resolve' => null],
                ];
            },
        ]);
        $barResolver = static function () {
            return 'bar';
        };
        $bazResolver = static function () {
            return 'baz';
        };

        $this->decorate(
            [$objectType->name => $objectType],
            [$objectType->name => ['bar' => $barResolver, 'baz' => $bazResolver]]
        );
        $fields = $objectType->config['fields']();

        foreach (['bar', 'baz'] as $fieldName) {
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
                    'bar' => function ($value, $args) {
                        return $args;
                    },
                ],
            ]
        );
        $expected = ['foo' => 'baz'];
        $resolveFn = $objectType->getField('bar')->resolveFn;
        /** @var Argument $args */
        $args = $resolveFn(null, $expected);
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
        $unionType = new UnionType(['name' => 'Foo']);
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
        $interfaceType = new InterfaceType(['name' => 'Foo']);
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
        $customScalarType = new CustomScalarType(['name' => 'Foo']);
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
            ['myType' => new InputObjectType(['name' => 'myType'])],
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
        $objectWithResolveField = new ObjectType(['name' => 'Bar', 'fields' => [], 'resolveField' => null]);

        return [
            // isTypeOf
            [ResolverMapInterface::IS_TYPEOF, new ObjectType(['name' => 'Foo', 'fields' => [], 'isTypeOf' => null])],
            // resolveField
            [
                ResolverMapInterface::RESOLVE_FIELD,
                $objectWithResolveField,
                function (ObjectType $type) {
                    return $type->resolveFieldFn;
                },
                false,
            ],
            [ResolverMapInterface::RESOLVE_FIELD, $objectWithResolveField, null, false],
            // resolveType
            [ResolverMapInterface::RESOLVE_TYPE, new UnionType(['name' => 'Baz', 'resolveType' => null])],
            [ResolverMapInterface::RESOLVE_TYPE, new InterfaceType(['name' => 'Baz', 'resolveType' => null])],
            // custom scalar
            [ResolverMapInterface::SERIALIZE, new CustomScalarType(['name' => 'Custom', 'serialize' => null])],
            [ResolverMapInterface::PARSE_VALUE, new CustomScalarType(['name' => 'Custom', 'parseValue' => null])],
            [ResolverMapInterface::PARSE_LITERAL, new CustomScalarType(['name' => 'Custom', 'parseLiteral' => null])],
            [ResolverMapInterface::SCALAR_TYPE, new CustomScalarType(['name' => 'Custom'])],
        ];
    }

    private function assertDecorateException(array $types, array $map, ?string $exception = null, ?string $exceptionMessage = null): void
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
