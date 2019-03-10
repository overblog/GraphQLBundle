<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\MultipleQueries;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class MultipleQueriesTest extends TestCase
{
    private const REQUIRED_FAILS_ERRORS = [
        [
            'message' => 'Internal server Error',
            'extensions' => ['category' => 'internal'],
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

    private const OPTIONAL_FAILS = [
        'errors' => [
            [
                'message' => 'Internal server Error',
                'extensions' => ['category' => 'internal'],
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

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel(['test_case' => 'multipleQueries']);
    }

    public function testRequiredFails(): void
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

    public function testOptionalFails(): void
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

    public function testMutationRequiredFails(): void
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

    public function testMutationOptionalFails(): void
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
