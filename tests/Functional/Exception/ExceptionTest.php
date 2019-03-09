<?php

namespace Overblog\GraphQLBundle\Tests\Functional\Exception;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;
use Overblog\GraphQLBundle\Tests\VersionHelper;

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
                'category' => 'user',
                'locations' => [
                    [
                        'line' => 2,
                        'column' => 5,
                    ],
                ],
                'path' => ['test'],
            ],
        ];

        $this->assertGraphQL($query, $expectedData, VersionHelper::normalizedErrors($expectedErrors));
    }
}
