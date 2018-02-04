<?php

namespace Overblog\GraphQLBundle\Tests\Upload\Type;

use GraphQL\Error\InvariantViolation;
use Overblog\GraphQLBundle\Upload\Type\GraphQLUploadType;
use PHPUnit\Framework\TestCase;

class GraphQLUploadTypeTest extends TestCase
{
    /**
     * @param mixed $invalidValue
     * @param $type
     *
     * @dataProvider invalidValueProvider
     */
    public function testInvalidParseValue($invalidValue, $type)
    {
        $this->expectException(InvariantViolation::class);
        $this->expectExceptionMessage(sprintf('Upload should be null or instance of "Symfony\Component\HttpFoundation\File\File" but %s given.', $type));
        (new GraphQLUploadType('Upload'))->parseValue($invalidValue);
    }

    public function invalidValueProvider()
    {
        yield ['str', 'string'];
        yield [1, 'integer'];
        yield [new \stdClass(), 'stdClass'];
        yield [true, 'boolean'];
        yield [false, 'boolean'];
    }
}
