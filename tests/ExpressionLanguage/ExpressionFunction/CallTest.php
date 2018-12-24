<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Call;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class CallTest extends TestCase
{
    protected function getFunctions()
    {
        return [new Call()];
    }

    public function method(string $arg)
    {
        return $arg.$arg;
    }

    public function testCall(): void
    {
        $this->assertEquals('AA', eval('$class = new '.self::class.'(); return '.$this->expressionLanguage->compile(\sprintf('call(%s.%s, ["A"])', 'class', 'method'), ['class']).';'));
        $this->assertEquals('AA', eval('return '.$this->expressionLanguage->compile(\sprintf('call("%s::%s", ["A"], true)', \str_replace('\\', '\\\\', \get_class($this)), 'method')).';'));
    }
}
