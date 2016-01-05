<?php

namespace Overblog\GraphBundle\Relay\Type;

use Overblog\GraphBundle\Definition\Builder\ObjectDefinition;

class Edge extends ObjectDefinition
{
    protected $nodeFieldDefinition = [];

    /**
     * @return array
     */
    public function getNodeFieldDefinition()
    {
        return $this->nodeFieldDefinition;
    }

    public function setNodeFieldDefinition(array $nodeFieldDefinition)
    {
        $this->nodeFieldDefinition =  $nodeFieldDefinition;

        return $this;
    }


    /**
     * @return array
     */
    public function fields()
    {
        return $this->nodeFieldDefinition + [
            'cursor' => ['type' => 'String!'],
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
