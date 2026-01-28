<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Generator;

use Generator;
use Overblog\GraphQLBundle\Event\SchemaCompiledEvent;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use Overblog\GraphQLBundle\Generator\Converter\ExpressionConverter;
use Overblog\GraphQLBundle\Generator\TypeBuilder;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Generator\TypeGeneratorOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class TypeGeneratorTest extends TestCase
{
    #[DataProvider('getPermissionsProvider')]
    public function testCacheDirPermissions(int $expectedMask, ?string $cacheDir, ?int $cacheDirMask): void
    {
        $typeBuilder = new TypeBuilder(
            new ExpressionConverter(new ExpressionLanguage()),
            'namespace'
        );
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $options = new TypeGeneratorOptions('App', $cacheDir, true, null, $cacheDirMask);

        $mask = (new TypeGenerator([], $typeBuilder, $eventDispatcher, $options))->getCacheDirMask();

        $this->assertSame($expectedMask, $mask);
    }

    public function testCompiledEvent(): void
    {
        $typeBuilder = new TypeBuilder(
            new ExpressionConverter(new ExpressionLanguage()),
            'namespace'
        );
        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        $eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo(new SchemaCompiledEvent()));

        $options = new TypeGeneratorOptions('App', null);

        (new TypeGenerator([], $typeBuilder, $eventDispatcher, $options))->compile(TypeGenerator::MODE_DRY_RUN);
    }

    public static function getPermissionsProvider(): Generator
    {
        // default permission when using default cache dir
        yield [0777, null, null];
        // default with custom cache dir path
        yield [0775, '/src', null];
        // custom permissions
        yield [0755, null, 0755];
    }
}
