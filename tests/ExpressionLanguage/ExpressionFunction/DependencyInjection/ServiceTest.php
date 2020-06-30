<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\DependencyInjection;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\DependencyInjection\Service;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class ServiceTest extends TestCase
{
    private $evaluationObject;

    protected function getFunctions()
    {
        $this->evaluationObject = new \stdClass();
        $container = $this->getDIContainerMock(['toto' => $this->evaluationObject]);

        return [
            new Service($container),
            new Service($container, 'serv'),
        ];
    }

    /**
     * @dataProvider getNames
     */
    public function testServiceCompilation(string $name): void
    {
        $object = new \stdClass();
        ${TypeGenerator::GLOBAL_VARS} = new GlobalVariables(['container' => $this->getDIContainerMock(['toto' => $object])]);
        ${TypeGenerator::GLOBAL_VARS}->get('container');
        $this->assertSame($object, eval('return '.$this->expressionLanguage->compile($name.'("toto")').';'));
    }

    /**
     * @dataProvider getNames
     */
    public function testServiceEvaluation(string $name): void
    {
        $this->assertSame($this->evaluationObject, $this->expressionLanguage->evaluate($name.'("toto")'));
    }

    public function getNames()
    {
        return [
            ['service'],
            ['serv'],
        ];
    }
}
