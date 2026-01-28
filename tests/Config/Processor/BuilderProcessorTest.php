<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Processor;

use InvalidArgumentException;
use Overblog\GraphQLBundle\Config\Processor\BuilderProcessor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

final class BuilderProcessorTest extends TestCase
{
    /**
     * @param string $name
     * @param string $type
     * @param string $builderClass
     * @param string $exceptionClass
     * @param string $exceptionMessage
     */
    #[DataProvider('apiAbuseProvider')]
    public function testApiAbuse($name, $type, $builderClass, $exceptionClass, $exceptionMessage): void
    {
        $this->expectException($exceptionClass); // @phpstan-ignore-line
        $this->expectExceptionMessage($exceptionMessage);
        BuilderProcessor::addBuilderClass($name, $type, $builderClass);
    }

    /**
     * @param string $exceptionClass
     * @param string $exceptionMessage
     */
    #[DataProvider('processApiAbuseProvider')]
    public function testProcessApiAbuse(array $config, $exceptionClass, $exceptionMessage): void
    {
        $this->expectException($exceptionClass); // @phpstan-ignore-line
        $this->expectExceptionMessage($exceptionMessage);
        BuilderProcessor::process($config);
    }

    public static function apiAbuseProvider(): array
    {
        return [
            ['foo', BuilderProcessor::BUILDER_FIELD_TYPE, 'Fake\Foo', InvalidArgumentException::class, 'Field builder class "Fake\Foo" not found.'],
            ['foo', BuilderProcessor::BUILDER_FIELDS_TYPE, 'Fake\Foo', InvalidArgumentException::class, 'Fields builder class "Fake\Foo" not found.'],
            ['foo', BuilderProcessor::BUILDER_FIELD_TYPE, stdClass::class, InvalidArgumentException::class, 'Field builder class should implement "Overblog\GraphQLBundle\Definition\Builder\MappingInterface", but "stdClass" given.'],
            ['foo', BuilderProcessor::BUILDER_FIELDS_TYPE, stdClass::class, InvalidArgumentException::class, 'Fields builder class should implement "Overblog\GraphQLBundle\Definition\Builder\MappingInterface", but "stdClass" given.'],
            ['foo', BuilderProcessor::BUILDER_ARGS_TYPE, stdClass::class, InvalidArgumentException::class, 'Args builder class should implement "Overblog\GraphQLBundle\Definition\Builder\MappingInterface", but "stdClass" given.'],
        ];
    }

    public static function processApiAbuseProvider(): array
    {
        return [
            [
                [
                    'foo' => [
                        'type' => 'object',
                        'config' => [
                            'fields' => ['id' => ['builder' => 'notExists']],
                        ],
                    ],
                ],
                InvalidConfigurationException::class,
                'Field builder "notExists" not found.',
            ],
            [
                [
                    'bar' => [
                        'type' => 'object',
                        'config' => [
                            'fields' => ['id' => ['argsBuilder' => 'notExists']],
                        ],
                    ],
                ],
                InvalidConfigurationException::class,
                'Args builder "notExists" not found.',
            ],
        ];
    }
}
