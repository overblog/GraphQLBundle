<?php

namespace Overblog\GraphQLBundle\Tests\Functional\MultipleQueries;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class MultipleQueriesTest extends TestCase
{
    const REQUIRED_FAILS_ERRORS = [
        [
            'message' => 'Internal server Error',
            'category' => 'internal',
            'locations' => [
                [
                    'line' => 2,
                    'column' => 3,
                ],
            ],
            'path' => [
                'fail',
            ],
        ],
    ];

    const OPTIONAL_FAILS = [
        'errors' => [
            [
                'message' => 'Internal server Error',
                'category' => 'internal',
                'locations' => [
                    [
                        'line' => 2,
                        'column' => 3,
                    ],
                ],
                'path' => [
                    'fail',
                ],
            ],
        ],
        'data' => [
            'fail' => null,
            'success' => 'foo',
        ],
    ];

    protected function setUp()
    {
        parent::setUp();

        static::bootKernel(['test_case' => 'multipleQueries']);
    }

    public function testRequiredFails()
    {
        $query = <<<'EOF'
{
  fail: failRequire
  success: success
}
EOF;
        $result = $this->executeGraphQLRequest($query);
        $this->assertSame(self::REQUIRED_FAILS_ERRORS, $result['errors']);
        $this->assertTrue(empty($result['data']));
    }

    public function testOptionalFails()
    {
        $query = <<<'EOF'
{
  fail: failOptional
  success: success
}
EOF;
        $result = $this->executeGraphQLRequest($query);
        $this->assertSame(self::OPTIONAL_FAILS, $result);
    }

    public function testMutationRequiredFails()
    {
        $query = <<<'EOF'
mutation {
  fail: failRequire
  success: success
}
EOF;
        $result = $this->executeGraphQLRequest($query);
        $this->assertSame(self::REQUIRED_FAILS_ERRORS, $result['errors']);
        $this->assertTrue(empty($result['data']));
    }

    public function testMutationOptionalFails()
    {
        $query = <<<'EOF'
mutation {
  fail: failOptional
  success: success
}
EOF;
        $result = $this->executeGraphQLRequest($query);
        $this->assertSame(self::OPTIONAL_FAILS, $result);
    }
}
