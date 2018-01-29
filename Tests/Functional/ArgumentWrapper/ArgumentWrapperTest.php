<?php

namespace Overblog\GraphQLBundle\Tests\Functional\AutoMapping;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class ArgumentWrapperTest extends TestCase
{
    protected function setUp()
    {
        static::bootKernel(['test_case' => 'argumentWrapper']);
    }

    public function testQuery()
    {
        $query = '{ fieldWithResolverAndArgument(name: "foo") fieldWithDefaultResolverAndArgument(name: "bar") field }';
        $expectedData = [
            'fieldWithResolverAndArgument' => 'Arguments: foo',
            'fieldWithDefaultResolverAndArgument' => 'Arguments: bar',
            'field' => 'Arguments: ',
        ];

        $this->assertGraphQL($query, $expectedData);
    }
}
