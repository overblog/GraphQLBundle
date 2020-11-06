<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\GraphQL;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\IsTypeOf;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;
use stdClass;

class IsTypeOfTest extends TestCase
{
    protected function getFunctions()
    {
        return [new IsTypeOf()];
    }

    public function testIsTypeOfCompile(): void
    {
        $this->assertTrue(eval('$value = new stdClass(); return '.$this->expressionLanguage->compile('isTypeOf("stdClass")', ['value']).';'));
    }

    /**
     * As evaluators of this bundle are used only by the Expression constraint
     * the name 'value' was replaced by 'parentValue' to avoid a conflict,
     * because constraints already use name 'value'.
     */
    public function testIsTypeOfEvaluate(): void
    {
        $this->assertTrue($this->expressionLanguage->evaluate('isTypeOf("stdClass")', ['parentValue' => new stdClass()]));
    }
}
