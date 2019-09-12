<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\GraphQL;

use Overblog\GraphQLBundle\ExpressionLanguage\Exception\EvaluatorIsNotAllowedException;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Resolver;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class ResolverTest extends TestCase
{
    protected function getFunctions()
    {
        return [new Resolver(), new Resolver('res')];
    }

    public function testEvaluatorThrowsException(): void
    {
        $this->expectException(EvaluatorIsNotAllowedException::class);
        $this->expressionLanguage->evaluate('resolver()');
    }

    public function testEvaluatorThrowsExceptionByAlias(): void
    {
        $this->expectException(EvaluatorIsNotAllowedException::class);
        $this->expressionLanguage->evaluate('res()');
    }
}
