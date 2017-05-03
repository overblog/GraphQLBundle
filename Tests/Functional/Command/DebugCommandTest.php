<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Functional\Command;

use Overblog\GraphQLBundle\Command\DebugCommand;
use Overblog\GraphQLBundle\Tests\Functional\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class DebugCommandTest extends TestCase
{
    /**
     * @var Command
     */
    private $command;

    /**
     * @var CommandTester
     */
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
            $this->logs[$category] = trim(str_replace('ø', '', file_get_contents(__DIR__.'/fixtures/debug-'.$category.'.txt')));
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
