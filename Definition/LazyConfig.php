<?php

namespace Overblog\GraphQLBundle\Definition;

final class LazyConfig
{
    /** @var \Closure */
    private $loader;

    /** @var  */
    private $vars;

    /**
     * @var callable
     */
    private $onPostLoad = [];

    private function __construct(\Closure $loader, array $vars = [])
    {
        $this->loader = $loader;
        $this->vars = new \ArrayObject($vars);
    }

    public static function create(\Closure $loader, array $vars = [])
    {
        return new self($loader, $vars);
    }

    /**
     * @return array
     */
    public function load()
    {
        $loader = $this->loader;
        $config = $loader($this->vars->getArrayCopy());
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
     * @return \Closure
     */
    public function getLoader()
    {
        return $this->loader;
    }

    public function setLoader(\Closure $loader)
    {
        $this->loader = $loader;
    }

    /**
     * @return \ArrayObject
     */
    public function getVars()
    {
        return $this->vars;
    }
}
