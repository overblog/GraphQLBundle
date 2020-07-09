<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\DependencyInjection;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\DependencyInjection\Service;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;
use stdClass;

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
     * @dataProvider getNames
     */
    public function testServiceCompilation(string $name): void
    {
        $object = new stdClass();
        ${TypeGenerator::GLOBAL_VARS} = new GlobalVariables(['container' => $this->getDIContainerMock(['toto' => $object])]);
        ${TypeGenerator::GLOBAL_VARS}->get('container');
        $this->assertSame($object, eval('return '.$this->expressionLanguage->compile($name.'("toto")').';'));
    }

    /**
     * @dataProvider getNames
     */
    public function testServiceEvaluation(string $name): void
    {
        $object = new stdClass();
        $globalVars = new GlobalVariables(['container' => $this->getDIContainerMock(['toto' => $object])]);
        $this->assertSame(
            $object,
            $this->expressionLanguage->evaluate($name.'("toto")', [TypeGenerator::GLOBAL_VARS => $globalVars])
        );
    }

    public function getNames(): array
    {
        return [
            ['service'],
            ['serv'],
        ];
    }
}
