<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Upload\Type;

use Generator;
use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use Overblog\GraphQLBundle\Upload\Type\GraphQLUploadType;
use PHPUnit\Framework\TestCase;
use stdClass;
use function sprintf;

class GraphQLUploadTypeTest extends TestCase
{
    /**
     * @param mixed $invalidValue
     *
     * @dataProvider invalidValueProvider
     *
     * @throws Error
     */
    public function testInvalidParseValue($invalidValue, string $type): void
    {
        $this->expectException(InvariantViolation::class);
        $this->expectExceptionMessage(sprintf('Upload should be null or instance of "Symfony\Component\HttpFoundation\File\File" but %s given.', $type));
        (new GraphQLUploadType('Upload'))->parseValue($invalidValue);
    }

    public function invalidValueProvider(): Generator
    {
        yield ['str', 'string'];
        yield [1, 'integer'];
        yield [new stdClass(), 'stdClass'];
        yield [true, 'boolean'];
        yield [false, 'boolean'];
    }
}
