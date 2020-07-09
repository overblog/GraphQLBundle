<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Definition\Type;

use Exception;
use Generator;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\ScalarTypeDefinitionNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use Overblog\GraphQLBundle\Definition\Type\CustomScalarType;
use Overblog\GraphQLBundle\Tests\Functional\App\Type\YearScalarType;
use PHPUnit\Framework\TestCase;
use stdClass;
use function sprintf;
use function uniqid;

class CustomScalarTypeTest extends TestCase
{
    public function testScalarTypeConfig(): void
    {
        $this->assertScalarTypeConfig(new YearScalarType());
        $this->assertScalarTypeConfig(function () {
            return new YearScalarType();
        });
    }

    public function testWithoutScalarTypeConfig(): void
    {
        $genericFunc = function ($value) {
            return $value;
        };
        $type = new CustomScalarType([
            'serialize' => $genericFunc,
            'parseValue' => $genericFunc,
            'parseLiteral' => $genericFunc,
        ]);

        foreach (['serialize', 'parseValue', 'parseLiteral'] as $field) {
            $value = new ScalarTypeDefinitionNode([]);
            $this->assertSame($value, $type->$field($value));
        }
    }

    /**
     * @param mixed  $scalarType
     * @param string $got
     *
     * @dataProvider invalidScalarTypeProvider
     */
    public function testAssertValidWithInvalidScalarType($scalarType, $got): void
    {
        $this->expectException(InvariantViolation::class);
        $name = uniqid('custom');
        $this->expectExceptionMessage(sprintf(
            '%s must provide a valid "scalarType" instance of %s but got: %s',
            $name,
            ScalarType::class,
            $got
        ));
        $type = new CustomScalarType(['name' => $name, 'scalarType' => $scalarType]);
        $type->assertValid();
    }

    public function testAssertValidSerializeFunctionIsRequired(): void
    {
        $this->expectException(InvariantViolation::class);
        $name = uniqid('custom');
        $this->expectExceptionMessage($name.' must provide "serialize" function. If this custom Scalar is also used as an input type, ensure "parseValue" and "parseLiteral" functions are also provided.');
        $type = new CustomScalarType(['name' => $name]);
        $type->assertValid();
    }

    public function invalidScalarTypeProvider(): Generator
    {
        yield [false, 'false'];
        yield [new stdClass(), 'instance of stdClass'];
        yield [
            function () {
                return false;
            },
            'false',
        ];
        yield [
            function () {
                return new stdClass();
            },
            'instance of stdClass',
        ];
    }

    /**
     * @param mixed $scalarType
     *
     * @throws Exception
     */
    private function assertScalarTypeConfig($scalarType): void
    {
        $type = new CustomScalarType([
            'scalarType' => $scalarType,
            'serialize' => function () {
                return 'serialize';
            },
            'parseValue' => function () {
                return 'parseValue';
            },
            'parseLiteral' => function () {
                return 'parseLiteral';
            },
        ]);

        $this->assertSame('50 AC', $type->serialize(50));
        $this->assertSame(50, $type->parseValue('50 AC'));
        $this->assertSame(50, $type->parseLiteral(new StringValueNode(['value' => '50 AC'])));
    }
}
