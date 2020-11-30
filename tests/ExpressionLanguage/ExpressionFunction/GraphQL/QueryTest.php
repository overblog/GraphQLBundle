<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\GraphQL;

use Overblog\GraphQLBundle\ExpressionLanguage\Exception\EvaluatorIsNotAllowedException;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Query;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class QueryTest extends TestCase
{
    protected function getFunctions()
    {
        return [new Query(), new Query('q')];
    }

    public function testEvaluatorThrowsException(): void
    {
        $this->expectException(EvaluatorIsNotAllowedException::class);
        $this->expressionLanguage->evaluate('query()');
    }

    public function testEvaluatorThrowsExceptionByAlias(): void
    {
        $this->expectException(EvaluatorIsNotAllowedException::class);
        $this->expressionLanguage->evaluate('q()');
    }
}
