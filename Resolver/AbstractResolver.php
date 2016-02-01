<?php

namespace Overblog\GraphBundle\Resolver;

use Overblog\GraphBundle\Resolver\Cache\CacheInterface;
use Overblog\GraphBundle\Resolver\Cache\ArrayCache;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractResolver implements ResolverInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var CacheInterface
     */
    protected $cache;

    public function __construct(ContainerInterface $container, CacheInterface $cache = null)
    {
        $this->container = $container;
        $this->cache = null !== $cache ? $cache : new ArrayCache();
    }
}
