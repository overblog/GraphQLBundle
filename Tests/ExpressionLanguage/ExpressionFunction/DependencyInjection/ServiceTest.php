<?php

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\DependencyInjection;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\DependencyInjection\Service;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class ServiceTest extends TestCase
{
    protected function getFunctions()
    {
        return [new Service(), new Service('serv')];
    }

    /**
     * @param string $name
     * @dataProvider getNames
     */
    public function testService($name)
    {
        $object = new \stdClass();
        $vars['container'] = $this->getDIContainerMock(['toto' => $object]);
        $this->assertSame($object, eval('return '.$this->expressionLanguage->compile($name.'("toto")').';'));
    }

    public function getNames()
    {
        return [
            ['service'],
            ['serv'],
        ];
    }
}
