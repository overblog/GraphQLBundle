<?php

namespace Overblog\GraphBundle\Relay\Node;

class GlobalId
{
    const SEPARATOR = ':';

    public static function toGlobalId($type, $id)
    {
        return base64_encode(sprintf('%s%s%s', $type, static::SEPARATOR, $id));
    }

    public static function fromGlobalId($globalId)
    {
        $unBasedGlobalId = base64_decode($globalId);

        list($type, $id) = array_merge(explode(static::SEPARATOR, $unBasedGlobalId), array( true ));

        return [
            'type' => $type,
            'id' => $id,
        ];
    }
}
