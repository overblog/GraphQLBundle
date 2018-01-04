<?php

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\GraphQL\Relay;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay\GlobalID;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class GlobalIDTest extends TestCase
{
    protected function getFunctions()
    {
        return [new GlobalID()];
    }

    public function testGlobalId()
    {
        $this->assertEquals('VXNlcjoxNQ==', eval('return '.$this->expressionLanguage->compile('globalId(15, "User")').';'));
    }
}
