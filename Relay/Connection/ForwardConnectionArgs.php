<?php

namespace Overblog\GraphQLBundle\Relay\Connection;

use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\ArgsInterface;

class ForwardConnectionArgs implements ArgsInterface
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
        ];
    }
}
