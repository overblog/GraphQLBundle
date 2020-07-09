<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\DependencyInjection\Compiler;

use InvalidArgumentException;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\GlobalVariablesPass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class GlobalVariablesPassTest extends TestCase
{
    /**
     * @param mixed $invalidAlias
     *
     * @dataProvider invalidAliasProvider
     */
    public function testInvalidAlias($invalidAlias): void
    {
        /** @var ContainerBuilder|MockObject $container */
        $container = $this->getMockBuilder(ContainerBuilder::class)
            ->setMethods(['findTaggedServiceIds', 'findDefinition'])
            ->getMock();
        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->willReturn([
                'my-id' => [
                    ['alias' => $invalidAlias],
                ],
            ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service "my-id" tagged "overblog_graphql.global_variable" should have a valid "alias" attribute.');

        (new GlobalVariablesPass())->process($container);
    }

    public function invalidAliasProvider(): array
    {
        return [
            [null],
            [new stdClass()],
            [[]],
            [true],
            [false],
            [''],
        ];
    }
}
