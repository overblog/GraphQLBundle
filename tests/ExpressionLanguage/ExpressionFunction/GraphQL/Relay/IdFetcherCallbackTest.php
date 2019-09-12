<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\GraphQL\Relay;

use Overblog\GraphQLBundle\ExpressionLanguage\Exception\EvaluatorIsNotAllowedException;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay\IdFetcherCallback;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class IdFetcherCallbackTest extends TestCase
{
    protected function getFunctions()
    {
        return [new IdFetcherCallback()];
    }

    public function testEvaluator(): void
    {
        $this->expectException(EvaluatorIsNotAllowedException::class);
        $this->expressionLanguage->evaluate('idFetcherCallback()');
    }
}
