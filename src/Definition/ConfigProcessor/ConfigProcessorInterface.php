<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\ConfigProcessor;

use Overblog\GraphQLBundle\Definition\LazyConfig;

interface ConfigProcessorInterface
{
    public function process(LazyConfig $lazyConfig): LazyConfig;
}
