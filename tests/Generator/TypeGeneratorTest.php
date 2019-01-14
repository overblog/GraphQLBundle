<?php

namespace Overblog\GraphQLBundle\Tests\Generator;

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
    public function testCacheDirPermissions($expectedMask, $cacheDir, $cacheDirMask)
    {
        $mask = (new TypeGenerator(
            'App', [], $cacheDir, [], true, null, null, $cacheDirMask
        ))->getCacheDirMask();

        $this->assertSame($expectedMask, $mask);
    }

    public function getPermissionsProvider()
    {
        // default permission when using default cache dir
        yield [0777, null, null];
        // default with custom cache dir path
        yield [0775, '/src', null];
        // custom permissions
        yield [0755, null, 0755];
    }
}
