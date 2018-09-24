<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Command;

use Overblog\GraphQLBundle\Command\DebugCommand;
use Overblog\GraphQLBundle\Tests\Functional\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\Kernel;

class DebugCommandTest extends TestCase
{
    /** @var Command */
    private $command;

    /** @var CommandTester */
    private $commandTester;

    private $logs = [];

    public function setUp(): void
    {
        parent::setUp();
        static::bootKernel(['test_case' => 'mutation']);

        $this->command = static::$kernel->getContainer()->get('overblog_graphql.command.debug');
        $this->commandTester = new CommandTester($this->command);

        foreach (DebugCommand::getCategories() as $category) {
            $content = \file_get_contents(
                \sprintf(
                    __DIR__.'/fixtures/debug/debug-%s.txt',
                    $category
                )
            );

            $this->logs[$category] = \trim($content);
        }
    }

    /**
     * @param array $categories
     * @dataProvider categoryDataProvider
     */
    public function testProcess(array $categories): void
    {
        if (empty($categories)) {
            $categories = DebugCommand::getCategories();
        }

        $this->commandTester->execute(['--category' => $categories]);
        $this->assertSame(0, $this->commandTester->getStatusCode());

        $expected = "\n";
        foreach ($categories as $category) {
            $expected .= $this->logs[$category]." \n\n\n\n";
        }

        $this->assertContains($expected, $this->commandTester->getDisplay(), '', \version_compare(Kernel::VERSION, '3.3.0') < 0);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid category (fake)
     */
    public function testInvalidFormat(): void
    {
        $this->commandTester->execute([
            '--category' => 'fake',
        ]);
    }

    public function categoryDataProvider()
    {
        return [
            [[]],
            [['type']],
            [['resolver']],
            [['mutation']],
            [['type', 'mutation']],
        ];
    }
}
