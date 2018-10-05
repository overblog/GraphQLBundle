<?php

namespace Overblog\GraphQLBundle\Config;

use Overblog\GraphQLBundle\Config\Processor\BuilderProcessor;
use Overblog\GraphQLBundle\Config\Processor\ExpressionProcessor;
use Overblog\GraphQLBundle\Config\Processor\InheritanceProcessor;
use Overblog\GraphQLBundle\Config\Processor\NamedConfigProcessor;
use Overblog\GraphQLBundle\Config\Processor\ProcessorInterface;
use Overblog\GraphQLBundle\Config\Processor\RelayProcessor;

class Processor implements ProcessorInterface
{
    const BEFORE_NORMALIZATION = 0;
    const NORMALIZATION = 2;

    const PROCESSORS = [
        self::BEFORE_NORMALIZATION => [
            RelayProcessor::class,
            NamedConfigProcessor::class,
            BuilderProcessor::class,
            InheritanceProcessor::class,
        ],
        self::NORMALIZATION => [ExpressionProcessor::class],
    ];

    public static function process(array $configs, $type = self::NORMALIZATION)
    {
        /** @var ProcessorInterface $processor */
        foreach (static::PROCESSORS[$type] as $processor) {
            $configs = \call_user_func([$processor, 'process'], $configs);
        }

        return $configs;
    }
}
