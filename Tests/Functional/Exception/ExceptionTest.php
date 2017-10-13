<?php

namespace Overblog\GraphQLBundle\Tests\Functional\Exception;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

/**
 * Class ConnectionTest.
 *
 * @see https://github.com/graphql/graphql-relay-js/blob/master/src/connection/__tests__/connection.js
 */
class ExceptionTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        static::bootKernel(['test_case' => 'exception']);
    }

    public function testExceptionIsMappedToAWarning()
    {
        $query = <<<'EOF'
query ExceptionQuery {
    test
}
EOF;

        $expectedData = [
            'test' => null,
        ];

        $expectedErrors = [
            [
                'message' => 'Invalid argument exception',
                'locations' => [
                    [
                        'line' => 2,
                        'column' => 5,
                    ],
                ],
                'path' => ['test'],
            ],
        ];

        $this->assertGraphQL($query, $expectedData, $expectedErrors);
    }
}
