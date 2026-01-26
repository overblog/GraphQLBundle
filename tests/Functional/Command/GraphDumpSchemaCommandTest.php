<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Command;

use Composer\InstalledVersions;
use InvalidArgumentException;
use Overblog\GraphQLBundle\Command\GraphQLDumpSchemaCommand;
use Overblog\GraphQLBundle\Tests\Functional\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

use function file_get_contents;
use function json_decode;
use function strcmp;
use function trim;
use function usort;

final class GraphDumpSchemaCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private string $cacheDir;

    public function setUp(): void
    {
        parent::setUp();
        static::bootKernel(['test_case' => 'connection']);

        $command = static::$kernel->getContainer()->get(GraphQLDumpSchemaCommand::class);
        $this->commandTester = new CommandTester($command);
        $this->cacheDir = static::$kernel->getCacheDir();
    }

    /**
     * @dataProvider formatDataProvider
     */
    public function testDump(string $format, bool $withFormatOption = true): void
    {
        $expectedFileName = $this->modifyExpectedFileNameForWebonyxPast1530(__DIR__.'/fixtures/schema.'.$format);

        $file = $this->cacheDir.'/schema.'.$format;

        $input = [
            '--file' => $file,
        ];

        if ($withFormatOption) {
            $input['--format'] = $format;
        }
        $this->assertCommandExecution(
            $input,
            $expectedFileName,
            $file,
            $format
        );
    }

    public function testDumpWithDescriptions(): void
    {
        $expectedFileName = $this->modifyExpectedFileNameForWebonyxPast1530(__DIR__.'/fixtures/schema.descriptions.json');

        $file = $this->cacheDir.'/schema.json';
        $this->assertCommandExecution(
            [
                '--file' => $file,
                '--with-descriptions' => true,
            ],
            $expectedFileName,
            $file,
            'json'
        );
    }

    public function testClassicJsonFormat(): void
    {
        $expectedFileName = $this->modifyExpectedFileNameForWebonyxPast1530(__DIR__.'/fixtures/schema.json');

        $file = $this->cacheDir.'/schema.json';
        $this->assertCommandExecution(
            [
                '--file' => $file,
                '--classic' => true,
                '--format' => 'json',
            ],
            $expectedFileName,
            $file,
            'json'
        );
    }

    public function testModernJsonFormat(): void
    {
        $expectedFileName = $this->modifyExpectedFileNameForWebonyxPast1530(__DIR__.'/fixtures/schema.modern.json');

        $file = $this->cacheDir.'/schema.json';
        $this->assertCommandExecution(
            [
                '--file' => $file,
                '--modern' => true,
                '--format' => 'json',
            ],
            $expectedFileName,
            $file,
            'json'
        );
    }

    public function testInvalidFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown format "fake"');
        $this->commandTester->execute([
            '--format' => 'fake',
        ]);
    }

    public function testInvalidModernAndClassicUsedTogether(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"modern" and "classic" options should not be used together.');
        $this->commandTester->execute([
            '--format' => 'json',
            '--classic' => true,
            '--modern' => true,
        ]);
    }

    public function formatDataProvider(): array
    {
        return [
            ['json', false],
            ['json', true],
            ['graphql'],
        ];
    }

    private function assertCommandExecution(array $input, string $expectedFile, string $actualFile, string $format, int $expectedStatusCode = 0): void
    {
        $this->commandTester->execute($input);

        $this->assertSame($expectedStatusCode, $this->commandTester->getStatusCode());
        $expected = trim((string) file_get_contents($expectedFile));
        $actual = trim((string) file_get_contents($actualFile));
        if ('json' === $format) {
            $expected = json_decode($expected, true);
            $actual = json_decode($actual, true);
            $this->sortSchemaEntry($expected, 'types', 'name');
            $this->sortSchemaEntry($actual, 'types', 'name');
        }
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    private function sortSchemaEntry(array &$entries, string $entryKey, string $sortBy): void
    {
        if (isset($entries['data']['__schema'][$entryKey])) {
            $data = &$entries['data']['__schema'][$entryKey];
        } else {
            $data = &$entries['__schema'][$entryKey];
        }

        usort($data, fn ($data1, $data2) => strcmp($data1[$sortBy], $data2[$sortBy]));
    }

    private function modifyExpectedFileNameForWebonyxPast1530(string $expectedFileName): string
    {
        $webOnyxVersion = InstalledVersions::getVersion('webonyx/graphql-php');
        $this->assertNotNull($webOnyxVersion, 'webonyx/graphql-php is not installed.');
        if (version_compare($webOnyxVersion, '15.30.0', '>=')) {
            $extension = pathinfo($expectedFileName, PATHINFO_EXTENSION);
            $pathWithoutExtension = substr($expectedFileName, 0, -1 * (strlen($extension) + 1));
            $expectedFileName = $pathWithoutExtension.'.pastv15.30.0.'.$extension;
        }

        return $expectedFileName;
    }
}
