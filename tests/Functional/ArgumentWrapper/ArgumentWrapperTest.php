<?php

namespace Overblog\GraphQLBundle\Tests\Functional\ArgumentWrapper;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class ArgumentWrapperTest extends TestCase
{
    protected function setUp()
    {
        static::bootKernel(['test_case' => 'argumentWrapper']);
    }

    public function testQuery()
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
