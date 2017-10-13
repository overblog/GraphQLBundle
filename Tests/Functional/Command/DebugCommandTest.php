<?php

namespace Overblog\GraphQLBundle\Tests\Functional\Command;

use Overblog\GraphQLBundle\Command\DebugCommand;
use Overblog\GraphQLBundle\Tests\Functional\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
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

    public function setUp()
    {
        parent::setUp();
        $client = static::createClient(['test_case' => 'mutation']);
        $kernel = $client->getKernel();

        $application = new Application($kernel);
        $application->add(new DebugCommand());
        $this->command = $application->find('graphql:debug');
        $this->commandTester = new CommandTester($this->command);
        foreach (DebugCommand::getCategories() as $category) {
            $this->logs[$category] = trim(
                file_get_contents(
                    sprintf(
                        __DIR__.'/fixtures/%sdebug-%s.txt',
                        version_compare(Kernel::VERSION, '3.3.0') >= 0 ? 'case-sensitive/' : '',
                        $category
                    )
                )
            );
        }
    }

    /**
     * @param array $categories
     * @dataProvider categoryDataProvider
     */
    public function testProcess(array $categories)
    {
        $input = [
            'command' => $this->command->getName(),
        ];
        if (empty($categories)) {
            $categories = DebugCommand::getCategories();
        } else {
            $input['--category'] = $categories;
        }

        $this->commandTester->execute($input);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $expected = "\n";
        foreach ($categories as $category) {
            $expected .= $this->logs[$category]." \n\n\n\n";
        }

        $this->assertEquals($expected, $this->commandTester->getDisplay());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid category (fake)
     */
    public function testInvalidFormat()
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
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
