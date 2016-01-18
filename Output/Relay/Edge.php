<?php

namespace Overblog\GraphBundle\Output\Relay;

final class Edge
{
    /** @var string  */
    public $cursor;

    /** @var  mixed */
    public $node;

    public function __construct($cursor, $node)
    {
        $this->cursor = $cursor;
        $this->node = $node;
    }
}
