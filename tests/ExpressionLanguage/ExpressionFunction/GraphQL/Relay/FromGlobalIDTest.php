<?php

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\GraphQL\Relay;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay\FromGlobalID;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class FromGlobalIDTest extends TestCase
{
    protected function getFunctions()
    {
        return [new FromGlobalID()];
    }

    public function testFromGlobalId()
    {
        $this->assertEquals(['type' => 'User', 'id' => 15], eval('return '.$this->expressionLanguage->compile('fromGlobalId("VXNlcjoxNQ==")').';'));
    }
}
