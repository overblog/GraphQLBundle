<?php

namespace Overblog\GraphQLBundle\Definition\ConfigProcessor;

use Overblog\GraphQLBundle\Definition\LazyConfig;

interface ConfigProcessorInterface
{
    /**
     * @param LazyConfig $lazyConfig
     *
     * @return LazyConfig
     */
    public function process(LazyConfig $lazyConfig);
}
