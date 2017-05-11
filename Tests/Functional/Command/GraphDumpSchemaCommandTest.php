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

use Overblog\GraphQLBundle\Command\GraphQLDumpSchemaCommand;
use Overblog\GraphQLBundle\Tests\Functional\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class GraphDumpSchemaCommandTest extends TestCase
{
    /**
     * @var Command
     */
    private $command;

    /**
     * @var CommandTester
     */
    private $commandTester;

    /**
     * @var string
     */
    private $cacheDir;

    public function setUp()
    {
        parent::setUp();
        $client = static::createClient(['test_case' => 'connection']);
        $kernel = $client->getKernel();

        $application = new Application($kernel);
        $application->add(new GraphQLDumpSchemaCommand());
        $this->command = $application->find('graphql:dump-schema');
        $this->commandTester = new CommandTester($this->command);
        $this->cacheDir = $kernel->getCacheDir();
    }

    /**
     * @param $format
     * @param bool $withFormatOption
     * @dataProvider formatDataProvider
     */
    public function testDump($format, $withFormatOption = true)
    {
        $file = $this->cacheDir.'/schema.'.$format;

        $input = [
            'command' => $this->command->getName(),
            '--file' => $file,
        ];

        if ($withFormatOption) {
            $input['--format'] = $format;
        }
        $this->assertCommandExecution(
            $input,
            __DIR__.'/fixtures/schema.'.$format,
            $file
        );
    }

    public function testClassicJsonFormat()
    {
        $file = $this->cacheDir.'/schema.json';
        $this->assertCommandExecution(
            [
                'command' => $this->command->getName(),
                '--file' => $file,
                '--classic' => true,
                '--format' => 'json',
            ],
            __DIR__.'/fixtures/schema.json',
            $file
        );
    }

    public function testModernJsonFormat()
    {
        $file = $this->cacheDir.'/schema.json';
        $this->assertCommandExecution(
            [
                'command' => $this->command->getName(),
                '--file' => $file,
                '--modern' => true,
                '--format' => 'json',
            ],
            __DIR__.'/fixtures/schema.modern.json',
            $file
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown format "fake".
     */
    public function testInvalidFormat()
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--format' => 'fake',
        ]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage "modern" and "classic" options should not be used together.
     */
    public function testInvalidModernAndClassicUsedTogether()
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--format' => 'json',
            '--classic' => true,
            '--modern' => true,
        ]);
    }

    public function formatDataProvider()
    {
        return [
            ['json', false],
            ['json', true],
            ['graphqls'],
        ];
    }

    private function assertCommandExecution(array $input, $expectedFile, $actualFile, $expectedStatusCode = 0)
    {
        $this->commandTester->execute($input);

        $this->assertEquals($expectedStatusCode, $this->commandTester->getStatusCode());
        $this->assertEquals(trim(file_get_contents($expectedFile)), trim(file_get_contents($actualFile)));
    }
}
