<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Call;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;
use function sprintf;
use function str_replace;

class CallTest extends TestCase
{
    protected function getFunctions(): array
    {
        return [new Call()];
    }

    public function method(string $arg): string
    {
        return $arg.$arg;
    }

    public static function staticMethod(string $arg): string
    {
        return $arg.$arg;
    }

    public function testCallCompile(): void
    {
        // Compile
        $this->assertEquals('AA', eval('
            $class = new '.self::class.'(); 
            return '.$this->expressionLanguage->compile('call(class.method, ["A"])', ['class']).';
         '));

        $this->assertEquals('AA', eval('return '.$this->expressionLanguage->compile(sprintf('call("%s::method", ["A"])', str_replace('\\', '\\\\', self::class))).';'));
    }

    public function testCallEvaluate(): void
    {
        // Static method using FQN
        $this->assertEquals('AA', $this->expressionLanguage->evaluate(sprintf('call("%s::staticMethod", ["A"])', str_replace('\\', '\\\\', self::class))));

        // Static method using array callable
        $this->assertEquals('AA', $this->expressionLanguage->evaluate(sprintf('call(["%s", "staticMethod"], ["A"])', str_replace('\\', '\\\\', self::class))));

        // Global function
        $this->assertEquals('AA', $this->expressionLanguage->evaluate('call("\implode", ["", ["A", "A"]])'));
    }
}
