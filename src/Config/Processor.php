<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config;

use Overblog\GraphQLBundle\Config\Processor\BuilderProcessor;
use Overblog\GraphQLBundle\Config\Processor\InheritanceProcessor;
use Overblog\GraphQLBundle\Config\Processor\NamedConfigProcessor;
use Overblog\GraphQLBundle\Config\Processor\ProcessorInterface;
use Overblog\GraphQLBundle\Config\Processor\RelayProcessor;

class Processor implements ProcessorInterface
{
    public const BEFORE_NORMALIZATION = 0;
    public const NORMALIZATION = 2;

    public const PROCESSORS = [
        self::BEFORE_NORMALIZATION => [
            RelayProcessor::class,
            BuilderProcessor::class,
            NamedConfigProcessor::class,
            InheritanceProcessor::class,
        ],
        self::NORMALIZATION => [],
    ];

    public static function process(array $configs, int $type = self::NORMALIZATION): array
    {
        /** @var ProcessorInterface $processor */
        foreach (static::PROCESSORS[$type] as $processor) {
            $configs = $processor::process($configs);
        }

        return $configs;
    }
}
