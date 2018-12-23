<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Relay\Connection\Output;

use Overblog\GraphQLBundle\Relay\Connection\Output\ConnectionBuilder;

/**
 * @group legacy
 */
class ConnectionBuilderFromPromisedTest extends \Overblog\GraphQLBundle\Tests\Relay\Connection\ConnectionBuilderFromPromisedTest
{
    public static function getBuilder()
    {
        return ConnectionBuilder::class;
    }
}
