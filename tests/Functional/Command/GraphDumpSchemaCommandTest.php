<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Command;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class GraphDumpSchemaCommandTest extends TestCase
{
    /** @var Command */
    private $command;

    /** @var CommandTester */
    private $commandTester;

    /** @var string */
    private $cacheDir;

    public function setUp(): void
    {
        parent::setUp();
        static::bootKernel(['test_case' => 'connection']);

        $this->command = static::$kernel->getContainer()->get('overblog_graphql.command.dump_schema');
        $this->commandTester = new CommandTester($this->command);
        $this->cacheDir = static::$kernel->getCacheDir();
    }

    /**
     * @param $format
     * @param bool $withFormatOption
     * @dataProvider formatDataProvider
     */
    public function testDump($format, $withFormatOption = true): void
    {
        $file = $this->cacheDir.'/schema.'.$format;

        $input = [
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

    public function testDumpWithDescriptions(): void
    {
        $file = $this->cacheDir.'/schema.json';
        $this->assertCommandExecution(
            [
                '--file' => $file,
                '--with-descriptions' => true,
            ],
            __DIR__.'/fixtures/schema.descriptions.json',
            $file,
            'json'
        );
    }

    public function testClassicJsonFormat(): void
    {
        $file = $this->cacheDir.'/schema.json';
        $this->assertCommandExecution(
            [
                '--file' => $file,
                '--classic' => true,
                '--format' => 'json',
            ],
            __DIR__.'/fixtures/schema.json',
            $file,
            'json'
        );
    }

    public function testModernJsonFormat(): void
    {
        $file = $this->cacheDir.'/schema.json';
        $this->assertCommandExecution(
            [
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
    public function testInvalidFormat(): void
    {
        $this->commandTester->execute([
            '--format' => 'fake',
        ]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage "modern" and "classic" options should not be used together.
     */
    public function testInvalidModernAndClassicUsedTogether(): void
    {
        $this->commandTester->execute([
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

    private function assertCommandExecution(array $input, $expectedFile, $actualFile, $format, $expectedStatusCode = 0): void
    {
        $this->commandTester->execute($input);

        $this->assertSame($expectedStatusCode, $this->commandTester->getStatusCode());
        $expected = \trim(\file_get_contents($expectedFile));
        $actual = \trim(\file_get_contents($actualFile));
        if ('json' === $format) {
            $expected = \json_decode($expected, true);
            $actual = \json_decode($actual, true);
            $this->sortSchemaEntry($expected, 'types', 'name');
            $this->sortSchemaEntry($actual, 'types', 'name');
        }
        $this->assertSame($expected, $actual);
    }

    private function sortSchemaEntry(array &$entries, $entryKey, $sortBy): void
    {
        if (isset($entries['data']['__schema'][$entryKey])) {
            $data = &$entries['data']['__schema'][$entryKey];
        } else {
            $data = &$entries['__schema'][$entryKey];
        }

        \usort($data, function ($data1, $data2) use ($sortBy) {
            return \strcmp($data1[$sortBy], $data2[$sortBy]);
        });
    }
}
