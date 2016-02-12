<?php

namespace Overblog\GraphQLBundle\Relay\Connection;

use GraphQLQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\ArgInterface;

class ForwardConnectionArgs implements ArgInterface
{
    /**
     * @param array $config
     * @return array
     */
    public function toArgDefinition(array $config)
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
