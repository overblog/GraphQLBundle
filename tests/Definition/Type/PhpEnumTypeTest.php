<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Definition\Type;

use GraphQL\Error\Error;
use GraphQL\Error\SerializationError;
use GraphQL\Language\AST\EnumValueNode;
use GraphQL\Language\AST\StringValueNode;
use Overblog\GraphQLBundle\Definition\Type\PhpEnumType;
use Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Enum\Color;
use PHPUnit\Framework\TestCase;

use function sprintf;

/**
 * @requires PHP 8.1
 */
final class PhpEnumTypeTest extends TestCase
{
    public function testInvalidEnumClass(): void
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessage(
            'Enum class "invalid_class" does not exist.',
        );
        new PhpEnumType([ // @phpstan-ignore-line
            'name' => 'MyEnum',
            'enumClass' => 'invalid_class',
        ]);
    }

    public function testInvalidEnumValueConfig(): void
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessage(
            'Enum value A is not defined in Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Enum\Color',
        );
        new PhpEnumType([
            'name' => 'MyEnum',
            'enumClass' => Color::class,
            'values' => [
                'A' => ['description' => 'Should fail'],
            ],
        ]);
    }

    protected function getEnum(): PhpEnumType
    {
        return new PhpEnumType([
            'name' => 'Color',
            'enumClass' => Color::class,
        ]);
    }

    public function testInvalidParseValue(): void
    {
        $invalidValue = 'invalidValue';
        $this->expectException(Error::class);
        $this->expectExceptionMessage(sprintf(
            'Cannot represent enum of class Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Enum\Color from value %s',
            $invalidValue
        ));

        $this->getEnum()->parseValue($invalidValue);
    }

    public function testValidParseValue(): void
    {
        $this->assertSame(Color::RED, $this->getEnum()->parseValue('RED'));
    }

    public function testInvalidSerialize(): void
    {
        $this->expectException(SerializationError::class);
        $this->expectExceptionMessage(sprintf(
            'Cannot serialize value "%s" as it must be an instance of enum %s.',
            PhpEnumTypeTest::class,
            Color::class,
        ));

        $this->getEnum()->serialize(self::class);
    }

    public function testValidSerialize(): void
    {
        $enum = new PhpEnumType([
            'name' => 'Color',
            'enumClass' => Color::class,
        ]);

        $this->assertEquals($enum->serialize(Color::BLUE), 'BLUE');
    }

    public function testInvalidLiteralNode(): void
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessage('Cannot represent enum of class');
        $this->getEnum()->parseLiteral(new StringValueNode(['value' => 'invalidValue']));
    }

    public function testInvalidLiteralValue(): void
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessage('Cannot represent enum of class');
        $this->getEnum()->parseLiteral(new EnumValueNode(['value' => 'invalidValue']));
    }
}
