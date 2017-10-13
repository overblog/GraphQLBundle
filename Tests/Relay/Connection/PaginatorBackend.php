<?php

namespace Overblog\GraphQLBundle\Tests\Relay\Connection;

/**
 * Class PaginatorBackend.
 */
class PaginatorBackend
{
    public function count($array)
    {
        return count($array);
    }
}
