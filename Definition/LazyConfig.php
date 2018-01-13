<?php

namespace Overblog\GraphQLBundle\Definition;

final class LazyConfig
{
    /** @var \Closure */
    private $loader;

    /** @var \ArrayObject */
    private $globalVariables;

    /**
     * @var callable
     */
    private $onPostLoad = [];

    private function __construct(\Closure $loader, array $globalVariables = [])
    {
        $this->loader = $loader;
        $this->globalVariables = new \ArrayObject($globalVariables);
    }

    public static function create(\Closure $loader, array $globalVariables = [])
    {
        return new self($loader, $globalVariables);
    }

    /**
     * @return array
     */
    public function load()
    {
        $loader = $this->loader;
        $config = $loader(new GlobalVariables($this->globalVariables->getArrayCopy()));
        foreach ($this->onPostLoad as $postLoader) {
            $config = $postLoader($config);
        }

        return $config;
    }

    public function addPostLoader(callable $postLoader)
    {
        $this->onPostLoad[] = $postLoader;
    }

    /**
     * @return \ArrayObject
     */
    public function getGlobalVariables()
    {
        return $this->globalVariables;
    }
}
