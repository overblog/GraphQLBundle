<?php

namespace Overblog\GraphQLBundle\Relay\Connection;

use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\ArgsInterface;

class ConnectionArgs implements ArgsInterface
{
    /**
     * @param array $config
     * @return array
     */
    public function toArgsDefinition(array $config)
    {
        return [
            'after' => [
                'type' => Type::string()
            ],
            'first' => [
                'type' => Type::int()
            ],
            'before' => [
                'type' => Type::string()
            ],
            'last' => [
                'type' => Type::int()
            ],
        ];
    }
}
