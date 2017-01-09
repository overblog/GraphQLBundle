<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

        static::createAndBootKernel(['test_case' => 'exception']);
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
            ],
        ];

        $this->assertGraphQL($query, $expectedData, $expectedErrors);
    }
}
