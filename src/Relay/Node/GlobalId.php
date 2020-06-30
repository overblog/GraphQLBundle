<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Node;

use function array_pad;
use function base64_decode;
use function base64_encode;
use function explode;
use function is_string;
use function sprintf;

class GlobalId
{
    public const SEPARATOR = ':';

    /**
     * @param mixed $type
     * @param mixed $id
     */
    public static function toGlobalId($type, $id): string
    {
        return base64_encode(sprintf('%s%s%s', $type, static::SEPARATOR, $id));
    }

    /**
     * @param mixed $globalId
     */
    public static function fromGlobalId($globalId): array
    {
        $unBasedGlobalId = is_string($globalId) ? base64_decode($globalId, true) : false;

        $decodeGlobalId = [
            'type' => null,
            'id' => null,
        ];

        if (false === $unBasedGlobalId) {
            return $decodeGlobalId;
        }

        list($decodeGlobalId['type'], $decodeGlobalId['id']) = array_pad(explode(static::SEPARATOR, $unBasedGlobalId, 2), 2, null);
        // transform empty string to null
        foreach ($decodeGlobalId as &$v) {
            $v = '' === $v ? null : $v;
        }

        return $decodeGlobalId;
    }
}
