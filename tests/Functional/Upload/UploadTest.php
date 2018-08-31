<?php

namespace Overblog\GraphQLBundle\Tests\Functional\Upload;

use GraphQL\Error\InvariantViolation;
use Overblog\GraphQLBundle\Tests\Functional\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadTest extends TestCase
{
    public function testSingleUpload()
    {
        $this->assertUpload(
            ['data' => ['singleUpload' => 'a.txt']],
            [
                'operations' => [
                    'query' => 'mutation($file: Upload!) { singleUpload(file: $file) }',
                    'variables' => ['file' => null],
                ],
                'map' => ['0' => ['variables.file']],
            ],
            ['0' => 'a.txt']
        );
    }

    public function testOptionalUpload()
    {
        $this->assertUpload(
            ['data' => ['singleUpload' => 'Sorry, No file was uploaded.']],
            [
                'operations' => [
                    'query' => 'mutation($file: Upload) { singleUpload(file: $file) }',
                    'variables' => ['file' => null],
                ],
                'map' => [],
            ],
            []
        );
    }

    public function testMultipleUpload()
    {
        $this->assertUpload(
            ['data' => ['multipleUpload' => ['b.txt', 'c.txt']]],
            [
                'operations' => [
                    'query' => 'mutation($files: [Upload!]!) { multipleUpload(files: $files) }',
                    'variables' => ['files' => [null, null]],
                ],
                'map' => ['0' => ['variables.files.0'], '1' => ['variables.files.1']],
            ],
            ['0' => 'b.txt', 1 => 'c.txt']
        );
    }

    public function testBatching()
    {
        $this->assertUpload(
            [
                [
                    'id' => 'singleUpload',
                    'payload' => ['data' => ['singleUpload' => 'a.txt']],
                ],
                [
                   'id' => 'multipleUpload',
                    'payload' => ['data' => ['multipleUpload' => ['b.txt', 'c.txt']]],
                ],
            ],
            [
                'operations' => [
                    ['id' => 'singleUpload', 'query' => 'mutation($file: Upload!) { singleUpload(file: $file) }', 'variables' => ['file' => null]],
                    ['id' => 'multipleUpload', 'query' => 'mutation($files: [Upload!]!) { multipleUpload(files: $files) }', 'variables' => ['files' => [null, null]]],
                ],
                'map' => ['0' => ['0.variables.file'], '1' => ['1.variables.files.0'], '2' => ['1.variables.files.1']],
            ],
            ['0' => 'a.txt', 1 => 'b.txt', 2 => 'c.txt'],
            '/batch'
        );
    }

    public function testOldUpload()
    {
        $this->assertUpload(
            ['data' => ['oldUpload' => 'a.txt']],
            [
                'query' => 'mutation($file: String!) { oldUpload(file: $file) }',
                'variables' => ['file' => 'a.txt'],
            ],
            ['0' => 'a.txt'],
            '/',
            false
        );
    }

    public function testSerializationIsUnsupported()
    {
        $this->expectException(InvariantViolation::class);
        $this->uploadRequest(
            [
                'operations' => [
                    'query' => 'mutation($file: Upload!) { serializationIsUnsupported(file: $file) }',
                    'variables' => ['file' => null],
                ],
                'map' => ['0' => ['variables.file']],
            ],
            ['0' => 'a.txt']
        );
    }

    public function testParseLiteralIsUnsupported()
    {
        $this->expectException(InvariantViolation::class);
        $this->expectExceptionMessage('Upload scalar literal unsupported.');
        $this->uploadRequest(
            [
                'operations' => [
                    'query' => 'mutation { singleUpload(file: {}) }',
                    'variables' => ['file' => null],
                ],
                'map' => ['0' => ['variables.file']],
            ],
            ['0' => 'a.txt']
        );
    }

    private function assertUpload(array $expected, array $parameters, array $files, $uri = '/', $json = true)
    {
        if ($json) {
            foreach ($parameters as &$parameter) {
                $parameter = \json_encode($parameter);
            }
        }
        $actual = $this->uploadRequest($parameters, $files, $uri);
        $this->assertSame($expected, $actual);
    }

    private function uploadRequest(array $parameters, array $files, $uri = '/')
    {
        $client = static::createClient(['test_case' => 'upload']);
        $client->request(
            'POST',
            $uri,
            $parameters,
            $this->createUploadedFiles($files),
            ['CONTENT_TYPE' => 'multipart/form-data']
        );

        return \json_decode($client->getResponse()->getContent(), true);
    }

    private function createUploadedFiles(array $fileNames)
    {
        $fixtureDir = __DIR__.'/fixtures/';
        $uploadedFiles = [];
        foreach ($fileNames as $key => $fileName) {
            $uploadedFiles[$key] = new UploadedFile($fixtureDir.'/'.$fileName, $fileName);
        }

        return $uploadedFiles;
    }
}
