<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\GraphQL\Relay;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay\ResolveSingleInputCallback;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class ResolveSingleInputCallbackTest extends TestCase
{
    protected function getFunctions()
    {
        return [new ResolveSingleInputCallback()];
    }

    public function testEvaluator()
    {
        $this->expectException(\RuntimeException::class);
        $this->expressionLanguage->evaluate("resolveSingleInputCallback()");
    }
}
