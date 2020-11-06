<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Relay\Connection\Cursor;

use Generator;
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

    public function valuesDataProvider(): Generator
    {
        yield [
            '000000',
            'MDAwMDAw',
            false,
        ];

        yield [
            "\0\0\0\0",
            'AAAAAA==',
            false,
        ];

        yield [
            "\xff",
            '/w==',
            false,
        ];

        yield [
            "\xff\xff",
            '//8=',
            false,
        ];

        yield [
            "\xff\xff\xff",
            '////',
            false,
        ];

        yield [
            "\xff\xff\xff\xff",
            '/////w==',
            false,
        ];

        yield [
            "\xfb",
            '+w==',
            false,
        ];

        yield [
            '',
            '',
            false,
        ];
    }
}
