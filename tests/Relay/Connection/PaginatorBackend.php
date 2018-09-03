<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Relay\Connection;

/**
 * Class PaginatorBackend.
 */
class PaginatorBackend
{
    public function count($array)
    {
        return \count($array);
    }
}
