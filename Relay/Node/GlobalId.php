<?php

namespace Overblog\GraphQLBundle\Relay\Node;

class GlobalId
{
    const SEPARATOR = ':';

    public static function toGlobalId($type, $id)
    {
        return base64_encode(sprintf('%s%s%s', $type, static::SEPARATOR, $id));
    }

    public static function fromGlobalId($globalId)
    {
        $unBasedGlobalId = base64_decode($globalId, true);

        $decodeGlobalId = [
            'type' => null,
            'id' => null,
        ];

        if (!$unBasedGlobalId) {
            return $decodeGlobalId;
        }

        list($decodeGlobalId['type'], $decodeGlobalId['id']) = array_pad(explode(static::SEPARATOR, $unBasedGlobalId), 2, null);

        return $decodeGlobalId;
    }
}
