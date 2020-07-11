<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Command;

use GraphQL\Error\InvariantViolation;
use Overblog\GraphQLBundle\Command\ValidateCommand;
use Overblog\GraphQLBundle\Definition\Type\ExtensibleSchema;
use Overblog\GraphQLBundle\Request\Executor;
use Overblog\GraphQLBundle\Tests\Functional\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use function trim;

class ValidateCommandTest extends TestCase
{
    /** @var ValidateCommand */
    private $command;

    /** @var CommandTester */
    private $commandTester;

    public function setUp(): void
    {
        parent::setUp();
        static::bootKernel(['test_case' => 'validation']);

        $this->command = static::$kernel->getContainer()->get(ValidateCommand::class);
        $this->commandTester = new CommandTester($this->command);
    }

    public function testValidSchema(): void
    {
        $this->commandTester->execute([]);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertEquals('No error', trim($this->commandTester->getDisplay()));
    }

    public function testValidSchemaThrowException(): void
    {
        $schema = $this->getMockBuilder(ExtensibleSchema::class)
            ->disableOriginalConstructor()
            ->setMethods(['assertValid'])
            ->getMock();
        $executor = $this->getMockBuilder(Executor::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSchema'])
            ->getMock();

        $executor->expects($this->once())->method('getSchema')
            ->with('foo')
            ->willReturn($schema);
        $schema->expects($this->once())->method('assertValid')
            ->willThrowException(new InvariantViolation('broken schema'));

        $this->command->setRequestExecutor($executor);

        $this->commandTester->execute(['--schema' => 'foo']);
        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertEquals('broken schema', trim($this->commandTester->getDisplay()));
    }
}
