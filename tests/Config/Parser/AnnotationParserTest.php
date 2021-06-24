<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser;

use Doctrine\Common\Annotations\Reader;
use Overblog\GraphQLBundle\Config\Parser\AnnotationParser;
use SplFileInfo;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class AnnotationParserTest extends MetadataParserTest
{
    public function parser(string $method, ...$args)
    {
        return AnnotationParser::$method(...$args);
    }

    public function formatMetadata(string $metadata): string
    {
        return sprintf('@%s', $metadata);
    }

    public function testNoDoctrineAnnotations(): void
    {
        if (class_exists(Reader::class)) {
            $this->markTestSkipped('doctrine/annotations are installed');
        }

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessageMatches('/doctrine\/annotations/');
        AnnotationParser::parse(new SplFileInfo(__DIR__.'/fixtures/annotations/Type/Animal.php'), $this->containerBuilder);
    }

    public function testNoSymfonyCache(): void
    {
        if (class_exists(AdapterInterface::class)) {
            $this->markTestSkipped('symfony/cache is installed');
        }

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessageMatches('/symfony\/cache/');
        AnnotationParser::parse(new SplFileInfo(__DIR__.'/fixtures/annotations/Type/Animal.php'), $this->containerBuilder);
    }

    public function testLegacyNestedAnnotations(): void
    {
        $this->config = self::cleanConfig($this->parser('parse', new SplFileInfo(__DIR__.'/fixtures/annotations/Deprecated/DeprecatedNestedAnnotations.php'), $this->containerBuilder, ['doctrine' => ['types_mapping' => []]]));
        $this->expect('DeprecatedNestedAnnotations', 'object', [
            'fields' => [
                'color' => ['type' => 'String!'],
                'getList' => [
                    'args' => [
                        'arg1' => ['type' => 'String!'],
                        'arg2' => ['type' => 'Int!'],
                    ],
                    'resolve' => '@=call(value.getList, arguments({arg1: "String!", arg2: "Int!"}, args))',
                    'type' => 'Boolean!',
                ],
            ],
            'builders' => [
                ['builder' => 'MyFieldsBuilder', 'builderConfig' => ['param1' => 'val1']],
            ],
        ]);
    }

    public function testLegacyFieldsBuilderAttributes(): void
    {
        $this->config = self::cleanConfig($this->parser('parse', new SplFileInfo(__DIR__.'/fixtures/annotations/Deprecated/DeprecatedBuilderAttributes.php'), $this->containerBuilder, ['doctrine' => ['types_mapping' => []]]));
        $this->expect('DeprecatedBuilderAttributes', 'object', [
            'fields' => [
                'color' => ['type' => 'String!'],
            ],
            'builders' => [
                ['builder' => 'MyFieldsBuilder', 'builderConfig' => ['param1' => 'val1']],
            ],
        ]);
    }

    public function testLegacyEnumNestedValue(): void
    {
        $this->config = self::cleanConfig($this->parser('parse', new SplFileInfo(__DIR__.'/fixtures/annotations/Deprecated/DeprecatedEnum.php'), $this->containerBuilder, ['doctrine' => ['types_mapping' => []]]));
        $this->expect('DeprecatedEnum', 'enum', [
            'values' => [
                'P1' => ['value' => 1, 'description' => 'P1 description'],
                'P2' => ['value' => 2, 'deprecationReason' => 'P2 deprecated'],
            ],
        ]);
    }
}
