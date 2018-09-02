<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Processor;

interface ProcessorInterface
{
    /**
     * @param array $configs
     *
     * @return array
     */
    public static function process(array $configs): array;
}
