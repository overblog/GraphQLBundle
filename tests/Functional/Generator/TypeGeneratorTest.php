<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Generator;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class TypeGeneratorTest extends TestCase
{
    public function testPublicCallback(): void
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

        $this->assertSame(
            'Cannot query field "privateData" on type "ObjectWithPrivateField".',
            \json_decode(
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

    public function testFieldDefaultPublic(): void
    {
        $this->assertSame(
            'Cannot query field "other" on type "ObjectWithPrivateField".',
            \json_decode(
                static::query(
                    'query { object { name other } }',
                    self::USER_RYAN,
                    'public'
                )->getResponse()->getContent(),
                true
            )['errors'][0]['message']
        );
    }

    /**
     * Defining the `cascade` validation option on
     * scalar types should throw an exception.
     */
    public function testCascadeOnScalarasThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cascade validation cannot be applied to built-in types.');

        parent::setUp();
        static::bootKernel(['test_case' => 'cascadeOnScalars']);
    }

    public function testNonExistentConstraintThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Constraint class 'Symfony\Component\Validator\Constraints\BlahBlah' doesn't exist.");

        parent::setUp();
        static::bootKernel(['test_case' => 'nonexistentConstraint']);
    }
}
