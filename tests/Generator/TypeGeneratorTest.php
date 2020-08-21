<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Generator;

use Generator;
use Overblog\GraphQLBundle\Event\SchemaCompiledEvent;
use Overblog\GraphQLBundle\Generator\TypeBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

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
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $mask = (new TypeGenerator(
            'App', $cacheDir, [], $typeBuilder, $eventDispatcher, true, null, $cacheDirMask
        ))->getCacheDirMask();

        $this->assertSame($expectedMask, $mask);
    }

    public function testCompiledEvent(): void
    {
        $typeBuilder = $this->createMock(TypeBuilder::class);
        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        $eventDispatcher->expects($this->once())->method('dispatch')->with($this->equalTo(new SchemaCompiledEvent()));

        (new TypeGenerator('App', null, [], $typeBuilder, $eventDispatcher))->compile(TypeGenerator::MODE_DRY_RUN);
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
