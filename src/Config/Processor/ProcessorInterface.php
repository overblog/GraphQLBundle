<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Processor;

interface ProcessorInterface
{
    public static function process(array $configs): array;
}
