<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Connection\Cursor;

/**
 * @phpstan-implements CursorEncoderInterface<string>
 */
final class PlainCursorEncoder implements CursorEncoderInterface
{
    /**
     * {@inheritdoc}
     */
    public function encode($value): string
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function decode(string $cursor)
    {
        return $cursor;
    }
}
