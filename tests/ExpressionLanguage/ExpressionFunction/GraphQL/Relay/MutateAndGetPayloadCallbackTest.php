<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\GraphQL\Relay;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay\MutateAndGetPayloadCallback;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class MutateAndGetPayloadCallbackTest extends TestCase
{
    protected function getFunctions()
    {
        return [new MutateAndGetPayloadCallback()];
    }

    public function testEvaluator()
    {
        $this->expectException(\RuntimeException::class);
        $this->expressionLanguage->evaluate("mutateAndGetPayloadCallback()");
    }
}
