<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Request;

use Generator;
use Overblog\GraphQLBundle\Request\UploadParserTrait;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use function json_encode;

class UploadParserTraitTest extends TestCase
{
    use UploadParserTrait;

    /**
     * @dataProvider locationsProvider
     */
    public function testLocationToPropertyAccessPath(string $location, string $expected): void
    {
        $actual = $this->locationToPropertyAccessPath($location);
        $this->assertSame($expected, $actual);
    }

    /**
     * @param string $message
     *
     * @dataProvider payloadProvider
     */
    public function testHandleUploadedFiles(array $operations, array $map, array $files, array $expected, $message): void
    {
        $actual = $this->handleUploadedFiles(['operations' => json_encode($operations), 'map' => json_encode($map)], $files);
        $this->assertSame($expected, $actual, $message);
    }

    public function testBindUploadedFilesFileNotFound(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('File 0 is missing in the request.');
        $operations = ['query' => '', 'variables' => ['file' => null]];
        $map = ['0' => ['variables.file']];
        $files = [];
        $this->bindUploadedFiles($operations, $map, $files);
    }

    public function testBindUploadedFilesOperationPathNotFound(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Map entry "variables.file" could not be localized in operations.');
        $operations = ['query' => '', 'variables' => []];
        $map = ['0' => ['variables.file']];
        $files = ['0' => new stdClass()];
        $this->bindUploadedFiles($operations, $map, $files);
    }

    public function testIsUploadPayload(): void
    {
        $this->assertFalse($this->isUploadPayload([]));
        $this->assertFalse($this->isUploadPayload(['operations' => []]));
        $this->assertFalse($this->isUploadPayload(['map' => []]));
        $this->assertFalse($this->isUploadPayload(['operations' => null, 'map' => []]));
        $this->assertFalse($this->isUploadPayload(['operations' => [], 'map' => null]));
        $this->assertFalse($this->isUploadPayload(['operations' => null, 'map' => null]));
        $this->assertFalse($this->isUploadPayload(['map' => [], 'operations' => []]), '"operations" must be place before "map".');
        $this->assertTrue($this->isUploadPayload(['operations' => [], 'map' => []]));
    }

    public function payloadProvider(): Generator
    {
        $files = ['0' => new stdClass()];
        yield [
            ['query' => 'mutation($file: Upload!) { singleUpload(file: $file) { id } }', 'variables' => ['file' => null]],
            ['0' => ['variables.file']],
            $files,
            ['query' => 'mutation($file: Upload!) { singleUpload(file: $file) { id } }', 'variables' => ['file' => $files['0']]],
            'single file',
        ];
        $files = ['0' => new stdClass(), 1 => new stdClass()];
        yield [
            ['query' => 'mutation($files: [Upload!]!) { multipleUpload(files: $files) { id } }', 'variables' => ['files' => [null, null]]],
            ['0' => ['variables.files.0'], '1' => ['variables.files.1']],
            $files,
            ['query' => 'mutation($files: [Upload!]!) { multipleUpload(files: $files) { id } }', 'variables' => ['files' => [$files['0'], $files[1]]]],
            'file list',
        ];
        $files = [0 => new stdClass(), '1' => new stdClass(), '2' => new stdClass()];
        yield [
            [
                ['query' => 'mutation($file: Upload!) { singleUpload(file: $file) { id } }', 'variables' => ['file' => null]],
                ['query' => 'mutation($files: [Upload!]!) { multipleUpload(files: $files) { id } }', 'variables' => ['files' => [null, null]]],
            ],
            ['0' => ['0.variables.file'], '1' => ['1.variables.files.0'], '2' => ['1.variables.files.1']],
            $files,
            [
                ['query' => 'mutation($file: Upload!) { singleUpload(file: $file) { id } }', 'variables' => ['file' => $files[0]]],
                ['query' => 'mutation($files: [Upload!]!) { multipleUpload(files: $files) { id } }', 'variables' => ['files' => [$files['1'], $files['2']]]],
            ],
            'batching',
        ];
    }

    public function locationsProvider(): Generator
    {
        yield ['variables.file', '[variables][file]'];
        yield ['variables.files.0', '[variables][files][0]'];
        yield ['variables.files.1', '[variables][files][1]'];
        yield ['0.variables.file', '[0][variables][file]'];
        yield ['1.variables.files.0', '[1][variables][files][0]'];
        yield ['1.variables.files.1', '[1][variables][files][1]'];
    }
}
