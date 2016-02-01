<?php

namespace Overblog\GraphBundle\Resolver\Cache;

interface CacheInterface
{
    /**
     * Saves a result in the cache.
     *
     * @param string $key        The cache key
     * @param mixed  $result
     */
    public function save($key, $result);

    /**
     * Fetches an result from the cache.
     *
     * @param string $key The cache key
     *
     * @return mixed|null
     */
    public function fetch($key);

    /**
     * Delete an result from the cache.
     *
     * @param string $key The cache key
     *
     * @return mixed|null
     */
    public function delete($key);
}
