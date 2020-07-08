<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\DependencyInjection;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\DependencyInjection\Service;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class ServiceTest extends TestCase
{
    protected function getFunctions()
    {
        return [
            new Service(),
            new Service('serv'),
        ];
    }

    /**
     * @param string $name
     * @dataProvider getNames
     */
    public function testServiceCompilation(string $name): void
    {
        $object = new \stdClass();
        $globalVariable = new GlobalVariables(['container' => $this->getDIContainerMock(['toto' => $object])]);
        $globalVariable->get('container');
        $this->assertSame($object, eval('return '.$this->expressionLanguage->compile($name.'("toto")').';'));
    }

    /**
     * @dataProvider getNames
     *
     * @param string $name
     */
    public function testServiceEvaluation(string $name): void
    {
        $object = new \stdClass();
        $globalVariable = new GlobalVariables(['container' => $this->getDIContainerMock(['toto' => $object])]);
        $this->assertSame(
            $object,
            $this->expressionLanguage->evaluate($name.'("toto")', ['globalVariable' => $globalVariable])
        );
    }

    public function getNames()
    {
        return [
            ['service'],
            ['serv'],
        ];
    }
}
