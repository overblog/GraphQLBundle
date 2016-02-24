<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Resolver\Cache;

interface CacheInterface
{
    /**
     * Saves a result in the cache.
     *
     * @param string $key    The cache key
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

    /**
     * reset cache.
     */
    public function clear();
}
