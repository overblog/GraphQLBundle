<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Connection\Cursor;

use Overblog\GraphQLBundle\Util\Base64Encoder;

/**
 * @phpstan-implements CursorEncoderInterface<string>
 */
final class Base64UrlSafeCursorEncoder implements CursorEncoderInterface
{
    /**
     * {@inheritdoc}
     */
    public function encode($value): string
    {
        return Base64Encoder::encodeUrlSafe($value);
    }

    /**
     * {@inheritdoc}
     */
    public function decode(string $cursor): string
    {
        return Base64Encoder::decodeUrlSafe($cursor);
    }
}
