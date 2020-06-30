<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Util;

use Generator;
use InvalidArgumentException;
use Overblog\GraphQLBundle\Util\Base64Encoder;
use PHPUnit\Framework\TestCase;

final class Base64UrlSafeEncoderTest extends TestCase
{
    /**
     * @dataProvider urlSafeValuesDataProvider
     */
    public function testEncodeUrlSafe(string $value, string $encodedValue, bool $usePadding): void
    {
        $this->assertSame($encodedValue, Base64Encoder::encodeUrlSafe($value, $usePadding));
    }

    /**
     * @dataProvider urlSafeValuesDataProvider
     */
    public function testDecodeUrlSafe(string $value, string $encodedValue): void
    {
        $this->assertSame($value, Base64Encoder::decodeUrlSafe($encodedValue));
    }

    public function urlSafeValuesDataProvider(): Generator
    {
        yield [
            '000000',
            'MDAwMDAw',
            false,
        ];

        yield [
            "\0\0\0\0",
            'AAAAAA',
            false,
        ];

        yield [
            "\xff",
            '_w',
            false,
        ];

        yield [
            "\xff\xff",
            '__8',
            false,
        ];

        yield [
            "\xff\xff\xff",
            '____',
            false,
        ];

        yield [
            "\xff\xff\xff\xff",
            '_____w',
            false,
        ];

        yield [
            "\xfb",
            '-w',
            false,
        ];

        yield [
            '',
            '',
            false,
        ];

        yield [
            'f',
            'Zg==',
            true,
        ];

        yield [
            'fo',
            'Zm8=',
            true,
        ];

        yield [
            'foo',
            'Zm9v',
            true,
        ];

        yield [
            'foob',
            'Zm9vYg==',
            true,
        ];

        yield [
            'fooba',
            'Zm9vYmE=',
            true,
        ];

        yield [
            'foobar',
            'Zm9vYmFy',
            true,
        ];
    }

    public function testDecodeUrlSafeWithWronglyPaddedString(): void
    {
        $this->assertSame('fooo', Base64Encoder::decodeUrlSafe('Zm9vbw='));
    }

    public function testDecodeUrlSafeThrowsOnInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "cxr0fdsezrewklerewxoz423ocfsa3bw432yjydsa9lhdsalw" value failed to be decoded from base64 format.');

        Base64Encoder::decodeUrlSafe('cxr0fdsezrewklerewxoz423ocfsa3bw432yjydsa9lhdsalw');
    }
}
