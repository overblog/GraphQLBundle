<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\ArgumentWrapper;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class ArgumentWrapperTest extends TestCase
{
    protected function setUp(): void
    {
        static::bootKernel(['test_case' => 'argumentWrapper']);
    }

    public function testQuery(): void
    {
        $query = '{ fieldWithResolverAndArgument(name: "foo") fieldWithDefaultResolverAndArgument(name: "bar") field fieldWithAccess}';
        $expectedData = [
            'fieldWithResolverAndArgument' => 'Field resolver Arguments: {"name":"foo"} | InstanceOf: true',
            'fieldWithDefaultResolverAndArgument' => 'Arguments: {"name":"bar"} | InstanceOf: true',
            'field' => 'Arguments: [] | InstanceOf: true',
            'fieldWithAccess' => 'Arguments: [] | InstanceOf: true',
        ];

        $this->assertGraphQL($query, $expectedData);
    }
}
