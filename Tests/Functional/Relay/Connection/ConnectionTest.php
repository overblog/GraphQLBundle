<?php

namespace Overblog\GraphQLBundle\Tests\Functional\Relay\Connection;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

/**
 * Class ConnectionTest
 * @see https://github.com/graphql/graphql-relay-js/blob/master/src/connection/__tests__/connection.js
 */
class ConnectionTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        static::$kernel = static::createKernel(['test_case' => 'connection']);
        static::$kernel->boot();
    }
}
