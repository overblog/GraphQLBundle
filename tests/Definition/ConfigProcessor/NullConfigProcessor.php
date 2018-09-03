<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Definition\ConfigProcessor;

use Overblog\GraphQLBundle\Definition\ConfigProcessor\ConfigProcessorInterface;
use Overblog\GraphQLBundle\Definition\LazyConfig;

class NullConfigProcessor implements ConfigProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(LazyConfig $lazyConfig): LazyConfig
    {
        return $lazyConfig;
    }
}
