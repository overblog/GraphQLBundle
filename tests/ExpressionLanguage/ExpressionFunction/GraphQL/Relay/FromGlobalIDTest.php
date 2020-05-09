<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\GraphQL\Relay;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay\FromGlobalID;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class FromGlobalIDTest extends TestCase
{
    protected function getFunctions()
    {
        return [new FromGlobalID()];
    }

    public function testFromGlobalIdCompile(): void
    {
        $this->assertSame(['type' => 'User', 'id' => '15'], eval('return '.$this->expressionLanguage->compile('fromGlobalId("VXNlcjoxNQ==")').';'));
    }

    public function testFromGlobalIdEvaluate(): void
    {
        $this->assertSame(['type' => 'User', 'id' => '15'], $this->expressionLanguage->evaluate('fromGlobalId("VXNlcjoxNQ==")'));
    }
}
