<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\GraphQL\Relay;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay\GlobalID;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class GlobalIDTest extends TestCase
{
    protected function getFunctions()
    {
        return [new GlobalID()];
    }

    public function testGlobalIdCompile(): void
    {
        $this->assertSame('VXNlcjoxNQ==', eval('return '.$this->expressionLanguage->compile('globalId(15, "User")').';'));
    }

    public function testGlobalIdEvaluate(): void
    {
        $this->assertSame('VXNlcjoxNQ==', $this->expressionLanguage->evaluate('globalId(15, "User")'));
    }
}
