<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
        $unBasedGlobalId = base64_decode($globalId, false);

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
