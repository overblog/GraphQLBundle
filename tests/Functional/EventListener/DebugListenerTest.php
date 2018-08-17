<?php

namespace Overblog\GraphQLBundle\Tests\Functional\EventListener;

use GraphQL\Type\Introspection;
use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class DebugListenerTest extends TestCase
{
    public function testDisabledDebugInfo()
    {
        $client = static::createClient(['test_case' => 'connection']);
        $response = $this->sendRequest($client, Introspection::getIntrospectionQuery(), true);
        $this->assertArrayNotHasKey('extensions', $response);
    }

    public function testEnabledDebugInfo()
    {
        $client = static::createClient(['test_case' => 'debug']);
        $response = $this->sendRequest($client, Introspection::getIntrospectionQuery(), true);
        $this->assertArrayHasKey('extensions', $response);
        $this->assertArrayHasKey('debug', $response['extensions']);
        $this->assertArrayHasKey('executionTime', $response['extensions']['debug']);
        $this->assertArrayHasKey('memoryUsage', $response['extensions']['debug']);
    }
}
