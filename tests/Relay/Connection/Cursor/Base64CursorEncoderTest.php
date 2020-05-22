<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Relay\Connection\Cursor;

use Overblog\GraphQLBundle\Relay\Connection\Cursor\Base64CursorEncoder;
use PHPUnit\Framework\TestCase;

final class Base64CursorEncoderTest extends TestCase
{
    /**
     * @var Base64CursorEncoder
     */
    private $encoder;

    protected function setUp(): void
    {
        $this->encoder = new Base64CursorEncoder();
    }

    /**
     * @dataProvider valuesDataProvider
     */
    public function testEncode(string $decodedValue, string $value): void
    {
        $this->assertSame($value, $this->encoder->encode($decodedValue));
    }

    /**
     * @dataProvider valuesDataProvider
     */
    public function testDecode(string $decodedValue, string $value): void
    {
        $this->assertSame($decodedValue, $this->encoder->decode($value));
    }

    public function valuesDataProvider(): \Generator
    {
        yield [
            '000000',
            'MDAwMDAw',
        ];

        yield [
            "\0\0\0\0",
            'AAAAAA',
        ];

        yield [
            "\xff",
            '_w',
        ];

        yield [
            "\xff\xff",
            '__8',
        ];

        yield [
            "\xff\xff\xff",
            '____',
        ];

        yield [
            "\xff\xff\xff\xff",
            '_____w',
        ];

        yield [
            "\xfb",
            '-w',
        ];

        yield [
            '',
            '',
        ];
    }
}
