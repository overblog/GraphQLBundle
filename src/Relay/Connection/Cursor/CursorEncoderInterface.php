<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Connection\Cursor;

/**
 * @phpstan-template T
 */
interface CursorEncoderInterface
{
    /**
     * @param mixed $value
     *
     * @phpstan-param T $value
     */
    public function encode($value): string;

    /**
     * @return mixed
     *
     * @phpstan-return T
     */
    public function decode(string $cursor);
}
