<?php

namespace Overblog\GraphQLBundle\Tests\Functional\Controller;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class GraphiQLControllerTest extends TestCase
{
    /**
     * @dataProvider graphiQLUriProvider
     */
    public function testIndexAction($uri)
    {
        $client = static::createClient();

        $client->request('GET', $uri);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function graphiQLUriProvider()
    {
        return [
            ['/graphiql'],
            ['/graphiql/default'],
        ];
    }
}
