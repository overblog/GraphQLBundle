<?php

namespace Overblog\GraphQLBundle\Tests\Config;

use Overblog\GraphQLBundle\Config\Processor\BuilderProcessor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class BuilderProcessorTest extends TestCase
{
    /**
     * @dataProvider apiAbuseProvider
     *
     * @param string $name
     * @param string $type
     * @param string $builderClass
     * @param string $exceptionClass
     * @param string $exceptionMessage
     */
    public function testApiAbuse($name, $type, $builderClass, $exceptionClass, $exceptionMessage)
    {
        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($exceptionMessage);
        BuilderProcessor::addBuilderClass($name, $type, $builderClass);
    }

    /**
     * @dataProvider processApiAbuseProvider
     *
     * @param array  $config
     * @param string $exceptionClass
     * @param string $exceptionMessage
     */
    public function testProcessApiAbuse(array $config, $exceptionClass, $exceptionMessage)
    {
        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($exceptionMessage);
        BuilderProcessor::process($config);
    }

    public function apiAbuseProvider()
    {
        return [
            ['foo', BuilderProcessor::BUILDER_FIELD_TYPE, [], \InvalidArgumentException::class, 'Field builder class should be string, but array given.'],
            ['foo', BuilderProcessor::BUILDER_ARGS_TYPE, [], \InvalidArgumentException::class, 'Args builder class should be string, but array given.'],
            ['bar', BuilderProcessor::BUILDER_FIELD_TYPE, null, \InvalidArgumentException::class, 'Field builder class should be string, but NULL given.'],
            ['foo', BuilderProcessor::BUILDER_FIELD_TYPE, 'Fake\Foo', \InvalidArgumentException::class, 'Field builder class "Fake\Foo" not found.'],
            ['foo', BuilderProcessor::BUILDER_FIELD_TYPE, \stdClass::class, \InvalidArgumentException::class, 'Field builder class should implement "Overblog\GraphQLBundle\Definition\Builder\MappingInterface", but "stdClass" given.'],
            ['foo', BuilderProcessor::BUILDER_ARGS_TYPE, \stdClass::class, \InvalidArgumentException::class, 'Args builder class should implement "Overblog\GraphQLBundle\Definition\Builder\MappingInterface", but "stdClass" given.'],
        ];
    }

    public function processApiAbuseProvider()
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
