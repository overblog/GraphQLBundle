<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Command;

use InvalidArgumentException;
use Overblog\GraphQLBundle\Command\DebugCommand;
use Overblog\GraphQLBundle\Tests\Functional\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use function file_get_contents;
use function sprintf;
use function str_replace;
use function trim;
use const PHP_EOL;

class DebugCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private array $logs = [];

    public function setUp(): void
    {
        parent::setUp();
        static::bootKernel(['test_case' => 'mutation']);

        $command = static::$kernel->getContainer()->get(DebugCommand::class);
        $this->commandTester = new CommandTester($command);

        foreach (DebugCommand::getCategories() as $category) {
            $content = file_get_contents(
                sprintf(
                    __DIR__.'/fixtures/debug/debug-%s.txt',
                    $category
                )
            );

            $this->logs[$category] = str_replace("\n", PHP_EOL, trim($content));
        }
    }

    /**
     * @dataProvider categoryDataProvider
     */
    public function testProcess(array $categories): void
    {
        if (empty($categories)) {
            $categories = DebugCommand::getCategories();
        }

        $this->commandTester->execute(['--category' => $categories]);
        $this->assertSame(0, $this->commandTester->getStatusCode());

        $expected = PHP_EOL;
        foreach ($categories as $category) {
            $expected .= $this->logs[$category].' '.PHP_EOL.PHP_EOL."\n\n";
        }

        $this->assertStringContainsString($expected, $this->commandTester->getDisplay());
    }

    public function testInvalidFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid category (fake)');
        $this->commandTester->execute([
            '--category' => 'fake',
        ]);
    }

    public function categoryDataProvider(): array
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
