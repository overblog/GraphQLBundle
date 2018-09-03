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

    public function testNewObject(): void
    {
        $this->assertInstanceOf('stdClass', eval('return '.$this->expressionLanguage->compile(\sprintf('newObject("%s")', 'stdClass')).';'));
    }
}
