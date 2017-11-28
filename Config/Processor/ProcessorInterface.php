<?php

namespace Overblog\GraphQLBundle\Config\Processor;

interface ProcessorInterface
{
    /**
     * @param array $configs
     *
     * @return array
     */
    public static function process(array $configs);
}
