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
        $unBasedGlobalId = base64_decode($globalId);

        list($type, $id) = array_merge(explode(static::SEPARATOR, $unBasedGlobalId), array(true));

        return [
            'type' => $type,
            'id'   => $id,
        ];
    }
}
