<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\NewObject;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class NewObjectTest extends TestCase
{
    protected function getFunctions()
    {
        return [new NewObject()];
    }

    public function testNewObjectCompile(): void
    {
        $this->assertInstanceOf('stdClass', eval('return '.$this->expressionLanguage->compile('newObject("stdClass")').';'));
    }

    public function testNewObjectEvaluate(): void
    {
        $this->assertInstanceOf('stdClass', $this->expressionLanguage->evaluate('newObject("stdClass")'));
    }
}
