<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Relay\Connection;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class PageInfoType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name'        => 'PageInfo',
            'description' => 'Information about pagination in a connection.',
            'fields'      => [
                'hasNextPage' => [
                    'type'        => Type::nonNull(Type::boolean()),
                    'description' => 'When paginating forwards, are there more items?',
                ],
                'hasPreviousPage' => [
                    'type'        => Type::nonNull(Type::boolean()),
                    'description' => 'When paginating backwards, are there more items?',

                ],
                'startCursor' => [
                    'type'        => Type::string(),
                    'description' => 'When paginating backwards, the cursor to continue.',
                ],
                'endCursor' => [
                    'type'        => Type::string(),
                    'description' => 'When paginating forwards, the cursor to continue.',
                ],
            ],
        ];

        parent::__construct($config);
    }
}
