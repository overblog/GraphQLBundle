<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\DependencyInjection;

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

        ${TypeGenerator::GRAPHQL_SERVICES} = $this->createGraphQLServices(['container' => $this->getDIContainerMock(['toto' => $object])]);
        ${TypeGenerator::GRAPHQL_SERVICES}->get('container');
        $this->assertSame($object, eval('return '.$this->expressionLanguage->compile($name.'("toto")').';'));
    }

    /**
     * @dataProvider getNames
     */
    public function testServiceEvaluation(string $name): void
    {
        $object = new stdClass();
        $services = $this->createGraphQLServices(['container' => $this->getDIContainerMock(['toto' => $object])]);

        $this->assertSame(
            $object,
            $this->expressionLanguage->evaluate($name.'("toto")', [TypeGenerator::GRAPHQL_SERVICES => $services])
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
