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
        $container = $this->getDIContainerMock();

        return [
            new Service($container),
            new Service($container, 'serv')
        ];
    }

    /**
     * @param string $name
     * @dataProvider getNames
     */
    public function testService($name): void
    {
        $object = new \stdClass();
        $globalVariable = new GlobalVariables(['container' => $this->getDIContainerMock(['toto' => $object])]);
        $globalVariable->get('container');
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
