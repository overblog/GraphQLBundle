<?php

namespace Overblog\GraphQLBundle\Relay\Connection;

use GraphQLQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\ArgInterface;

class BackwardConnectionArgs implements ArgInterface
{
    /**
     * @param array $config
     * @return array
     */
    public function toArgDefinition(array $config)
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
