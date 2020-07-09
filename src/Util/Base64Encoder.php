<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Util;

use InvalidArgumentException;
use function base64_decode;
use function base64_encode;
use function sprintf;
use function str_pad;
use function str_replace;
use function strlen;
use function substr_compare;

final class Base64Encoder
{
    public static function encode(string $value): string
    {
        return base64_encode($value);
    }

    public static function decode(string $value, bool $strict = true): string
    {
        $result = base64_decode($value, $strict);

        if (false === $result) {
            throw new InvalidArgumentException(sprintf('The "%s" value failed to be decoded from base64 format.', $value));
        }

        return $result;
    }

    public static function encodeUrlSafe(string $value, bool $padding = false): string
    {
        $result = base64_encode($value);
        $result = str_replace(['+', '/'], ['-', '_'], $result);

        if (!$padding) {
            $result = str_replace('=', '', $result);
        }

        return $result;
    }

    public static function decodeUrlSafe(string $value, bool $strict = true): string
    {
        $value = str_replace(['-', '_'], ['+', '/'], $value);

        if (0 === substr_compare($value, '=', -1) && 0 !== strlen($value) % 4) {
            $value = str_pad($value, (strlen($value) + 3) & ~3, '=');
        }

        $result = base64_decode($value, $strict);

        if (false === $result) {
            throw new InvalidArgumentException(sprintf('The "%s" value failed to be decoded from base64 format.', $value));
        }

        return $result;
    }
}
