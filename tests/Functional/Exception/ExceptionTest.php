<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Exception;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class ExceptionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel(['test_case' => 'exception']);
    }

    public function testExceptionIsMappedToAWarning(): void
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
                'extensions' => ['category' => 'user'],
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
