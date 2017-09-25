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
            $file,
            $format
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
            $file,
            'json'
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
            $file,
            'json'
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
            ['graphql'],
        ];
    }

    private function assertCommandExecution(array $input, $expectedFile, $actualFile, $format, $expectedStatusCode = 0)
    {
        $this->commandTester->execute($input);

        $this->assertEquals($expectedStatusCode, $this->commandTester->getStatusCode());
        $expected = trim(file_get_contents($expectedFile));
        $actual = trim(file_get_contents($actualFile));
        if ('json' === $format) {
            $expected = json_decode($expected, true);
            $actual = json_decode($actual, true);
            $this->sortSchemaEntry($expected, 'types', 'name');
            $this->sortSchemaEntry($actual, 'types', 'name');
        }

        $this->assertEquals($expected, $actual);
    }

    private function sortSchemaEntry(array &$entries, $entryKey, $sortBy)
    {
        if (isset($entries['data']['__schema'][$entryKey])) {
            $data = &$entries['data']['__schema'][$entryKey];
        } else {
            $data = &$entries['__schema'][$entryKey];
        }

        usort($data, function ($data1, $data2) use ($sortBy) {
            return strcmp($data1[$sortBy], $data2[$sortBy]);
        });
    }
}
