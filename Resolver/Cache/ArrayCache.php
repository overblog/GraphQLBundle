<?php

namespace Overblog\GraphQLBundle\Resolver\Cache;

class ArrayCache implements CacheInterface
{
    /**
     * @var array
     */
    private $cache = array();

    /**
     * {@inheritdoc}
     */
    public function fetch($key)
    {
        return isset($this->cache[$key]) ? $this->cache[$key] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function save($key, $result)
    {
        $this->cache[$key] = $result;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        unset($this->cache[$key]);
    }
}
