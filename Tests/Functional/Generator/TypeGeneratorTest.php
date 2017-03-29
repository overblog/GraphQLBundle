<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Functional\Generator;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class TypeGeneratorTest extends TestCase
{
    private $user = 'ryan';
    private $adminUser = 'admin';

    public function testPublicCallback()
    {
        $expected = [
            'data' => [
                'object' => [
                    'name' => 'His name',
                    'privateData' => 'ThisIsPrivate',
                ],
            ],
        ];

        $client = static::query(
            'query { object { name privateData } }',
            $this->adminUser
        );

        $this->assertResponse('query { object { name privateData } }', $expected, $this->adminUser);

        $this->assertEquals(
            'Cannot query field "privateData" on type "ObjectWithPrivateField".',
            json_decode(
                static::query(
                    'query { object { name privateData } }',
                    $this->user
                )->getResponse()->getContent(),
                true
            )['errors'][0]['message']
        );

        $expectedWithoutPrivateData = $expected;
        unset($expectedWithoutPrivateData['data']['object']['privateData']);

        $this->assertResponse('query { object { name } }', $expectedWithoutPrivateData, $this->user);
    }

    private static function assertResponse($query, array $expected, $username)
    {
        $client = self::query($query, $username);
        $result = $client->getResponse()->getContent();

        static::assertEquals($expected, json_decode($result, true), $result);

        return $client;
    }

    private static function query($query, $username)
    {
        $client = self::createClientAuthenticated($username);
        $client->request('GET', '/', ['query' => $query]);

        return $client;
    }

    private static function createClientAuthenticated($username)
    {
        $client = static::createClient(['test_case' => 'public']);

        if ($username) {
            $client->setServerParameters([
                'PHP_AUTH_USER' => $username,
                'PHP_AUTH_PW' => '123',
            ]);
        }

        return $client;
    }
}
