<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Generator;

use Generator;
use Overblog\GraphQLBundle\Event\SchemaCompiledEvent;
use Overblog\GraphQLBundle\Generator\TypeBuilder;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Generator\TypeGeneratorOptions;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class TypeGeneratorTest extends TestCase
{
    /**
     * @dataProvider getPermissionsProvider
     */
    public function testCacheDirPermissions(int $expectedMask, ?string $cacheDir, ?int $cacheDirMask): void
    {
        $typeBuilder = $this->createMock(TypeBuilder::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $options = new TypeGeneratorOptions('App', $cacheDir, [], true, null, $cacheDirMask);

        $mask = (new TypeGenerator($typeBuilder, $eventDispatcher, $options))->options->cacheDirMask;

        $this->assertSame($expectedMask, $mask);
    }

    public function testCompiledEvent(): void
    {
        $typeBuilder = $this->createMock(TypeBuilder::class);
        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        $eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo(new SchemaCompiledEvent()));

        $options = new TypeGeneratorOptions('App', null, []);

        (new TypeGenerator($typeBuilder, $eventDispatcher, $options))->compile(TypeGenerator::MODE_DRY_RUN);
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
