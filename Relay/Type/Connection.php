<?php

namespace Overblog\GraphBundle\Relay\Type;

use Overblog\GraphBundle\Definition\Builder\ObjectDefinition;

class Connection extends ObjectDefinition
{
    /**
     * @return array
     */
    public function fields()
    {
        return [
            'edges' => ['type' => '[Edge]'],
            'pageInfo' => ['type' => 'pageInfo'],
        ];
    }

    /**
     * @return array
     */
    public function interfaces()
    {
        return [];
    }
}
