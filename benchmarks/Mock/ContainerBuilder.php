<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Benchmarks\Mock;

use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder as BaseContainerBuilder;

class ContainerBuilder extends BaseContainerBuilder
{
    public function __construct()
    {
    }

    public function addResource(ResourceInterface $resource)
    {
        return $this;
    }
}
