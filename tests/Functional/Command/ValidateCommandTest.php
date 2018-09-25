<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Command;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ValidateCommandTest extends TestCase
{
    /** @var Command */
    private $command;

    /** @var CommandTester */
    private $commandTester;

    public function setUp(): void
    {
        parent::setUp();
        static::bootKernel(['test_case' => 'validation']);

        $this->command = static::$kernel->getContainer()->get('overblog_graphql.command.validate');
        $this->commandTester = new CommandTester($this->command);
    }

    public function testValidSchema(): void
    {
        $this->commandTester->execute([]);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertEquals('No error', \trim($this->commandTester->getDisplay()));
    }
}
