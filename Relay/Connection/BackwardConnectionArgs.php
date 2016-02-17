<?php

namespace Overblog\GraphQLBundle\Relay\Connection;

use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\ArgsInterface;

class BackwardConnectionArgs implements ArgsInterface
{
    /**
     * @param array $config
     * @return array
     */
    public function toArgsDefinition(array $config)
    {
        return [
            'before' => [
                'type' => Type::string()
            ],
            'last' => [
                'type' => Type::int()
            ],
        ];
    }
}
