<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Generator;

use Generator;
use Overblog\GraphQLBundle\Generator\TypeBuilder;
use PHPUnit\Framework\TestCase;

class TypeGeneratorTest extends TestCase
{
    /**
     * @param int         $expectedMask
     * @param string|null $cacheDir
     * @param int|null    $cacheDirMask
     *
     * @dataProvider getPermissionsProvider
     */
    public function testCacheDirPermissions($expectedMask, $cacheDir, $cacheDirMask): void
    {
        $typeBuilder = $this->createMock(TypeBuilder::class);

        $mask = (new TypeGenerator(
            'App', $cacheDir, [], $typeBuilder, true, null, $cacheDirMask
        ))->getCacheDirMask();

        $this->assertSame($expectedMask, $mask);
    }

    public function getPermissionsProvider(): Generator
    {
        // default permission when using default cache dir
        yield [0777, null, null];
        // default with custom cache dir path
        yield [0775, '/src', null];
        // custom permissions
        yield [0755, null, 0755];
    }
}
