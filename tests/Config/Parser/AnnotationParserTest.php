<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser;

use Overblog\GraphQLBundle\Config\Parser\AnnotationParser;
use SplFileInfo;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class AnnotationParserTest extends MetadataParserTest
{
    public function setUp(): void
    {
        if ('testNoDoctrineAnnotations' !== $this->getName()) {
            parent::setUp();
        }
    }

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
        if (self::isDoctrineAnnotationInstalled()) {
            $this->markTestSkipped('doctrine/annotations are installed');
        }

        try {
            $containerBuilder = $this->getMockBuilder(ContainerBuilder::class)->disableOriginalConstructor()->getMock();
            AnnotationParser::parse(new SplFileInfo(__DIR__.'/fixtures/annotations/Type/Animal.php'), $containerBuilder);
        } catch (InvalidArgumentException $e) {
            $this->assertInstanceOf(ServiceNotFoundException::class, $e->getPrevious());
            $this->assertMatchesRegularExpression('/doctrine\/annotations/', $e->getPrevious()->getMessage());
        }
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
