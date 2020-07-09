<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Util;

use Generator;
use InvalidArgumentException;
use Overblog\GraphQLBundle\Util\Base64Encoder;
use PHPUnit\Framework\TestCase;

final class Base64EncoderTest extends TestCase
{
    /**
     * @dataProvider valuesDataProvider
     */
    public function testEncode(string $value, string $encodedValue): void
    {
        $this->assertSame($encodedValue, Base64Encoder::encode($value));
    }

    /**
     * @dataProvider valuesDataProvider
     */
    public function testDecode(string $value, string $encodedValue): void
    {
        $this->assertSame($value, Base64Encoder::decode($encodedValue));
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

    public function testDecodeThrowsOnInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "cxr0fdsezrewklerewxoz423ocfsa3bw432yjydsa9lhdsalw" value failed to be decoded from base64 format.');

        Base64Encoder::decode('cxr0fdsezrewklerewxoz423ocfsa3bw432yjydsa9lhdsalw');
    }
}
