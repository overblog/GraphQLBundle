<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\GraphQL;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\IsTypeOf;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class IsTypeOfTest extends TestCase
{
    protected function getFunctions()
    {
        return [new IsTypeOf()];
    }

    public function testIsTypeOf(): void
    {
        $this->assertTrue(eval('$value = new \stdClass(); return '.$this->expressionLanguage->compile(\sprintf('isTypeOf("%s")', 'stdClass'), ['value']).';'));
    }
}
