<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Node;

class GlobalId
{
    public const SEPARATOR = ':';

    public static function toGlobalId($type, $id)
    {
        return \base64_encode(\sprintf('%s%s%s', $type, static::SEPARATOR, $id));
    }

    public static function fromGlobalId($globalId)
    {
        $unBasedGlobalId = \is_string($globalId) ? \base64_decode($globalId, true) : false;

        $decodeGlobalId = [
            'type' => null,
            'id' => null,
        ];

        if (false === $unBasedGlobalId) {
            return $decodeGlobalId;
        }

        list($decodeGlobalId['type'], $decodeGlobalId['id']) = \array_pad(\explode(static::SEPARATOR, $unBasedGlobalId, 2), 2, null);
        // transform empty string to null
        foreach ($decodeGlobalId as &$v) {
            $v = '' === $v ? null : $v;
        }

        return $decodeGlobalId;
    }
}
