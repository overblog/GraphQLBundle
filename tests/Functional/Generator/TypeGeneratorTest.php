<?php

namespace Overblog\GraphQLBundle\Tests\Functional\Generator;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class TypeGeneratorTest extends TestCase
{
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

        $this->assertResponse('query { object { name privateData } }', $expected, self::USER_ADMIN, 'public');

        $this->assertEquals(
            'Cannot query field "privateData" on type "ObjectWithPrivateField".',
            json_decode(
                static::query(
                    'query { object { name privateData } }',
                    self::USER_RYAN,
                    'public'
                )->getResponse()->getContent(),
                true
            )['errors'][0]['message']
        );

        $expectedWithoutPrivateData = $expected;
        unset($expectedWithoutPrivateData['data']['object']['privateData']);

        $this->assertResponse('query { object { name } }', $expectedWithoutPrivateData, self::USER_RYAN, 'public');
    }

    public function testFieldDefaultPublic()
    {
        $this->assertEquals(
            'Cannot query field "other" on type "ObjectWithPrivateField".',
            json_decode(
                static::query(
                    'query { object { name other } }',
                    self::USER_RYAN,
                    'public'
                )->getResponse()->getContent(),
                true
            )['errors'][0]['message']
        );
    }
}
