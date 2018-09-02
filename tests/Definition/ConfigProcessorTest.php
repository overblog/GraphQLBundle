<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Definition;

use Overblog\GraphQLBundle\Definition\ConfigProcessor;
use Overblog\GraphQLBundle\Definition\LazyConfig;
use Overblog\GraphQLBundle\Tests\Definition\ConfigProcessor\NullConfigProcessor;
use PHPUnit\Framework\TestCase;

class ConfigProcessorTest extends TestCase
{
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Registering config processor after calling process() is not supported.
     */
    public function testThrowExceptionWhenAddingAConfigProcessorAfterInitialization(): void
    {
        $configProcessor = new ConfigProcessor();
        $configProcessor->addConfigProcessor(new NullConfigProcessor());

        $configProcessor->process(LazyConfig::create(function () {
            return [];
        }));

        $configProcessor->addConfigProcessor(new NullConfigProcessor());
    }

    public function testOrderByPriorityDesc(): void
    {
        $configProcessor = new ConfigProcessor();

        $configProcessor->addConfigProcessor($nullConfigProcessor1 = new NullConfigProcessor(), 2);
        $configProcessor->addConfigProcessor($nullConfigProcessor2 = new NullConfigProcessor(), 4);
        $configProcessor->addConfigProcessor($nullConfigProcessor3 = new NullConfigProcessor(), 256);
        $configProcessor->addConfigProcessor($nullConfigProcessor4 = new NullConfigProcessor());
        $configProcessor->addConfigProcessor($nullConfigProcessor5 = new NullConfigProcessor(), 512);

        $configProcessor->process(LazyConfig::create(function () {
            return [];
        }));

        $processors = $configProcessor->getOrderedProcessors();

        $this->assertSame(
            $processors,
            [$nullConfigProcessor5, $nullConfigProcessor3, $nullConfigProcessor2, $nullConfigProcessor1, $nullConfigProcessor4]
        );
    }
}
