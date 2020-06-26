<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition;

final class LazyConfig
{
    private \Closure $loader;
    private ?GlobalVariables $globalVariables;

    /**
     * @var callable[]
     */
    private $onPostLoad = [];

    private function __construct(\Closure $loader, GlobalVariables $globalVariables = null)
    {
        $this->loader = $loader;
        $this->globalVariables = $globalVariables ?: new GlobalVariables();
    }

    public static function create(\Closure $loader, GlobalVariables $globalVariables = null)
    {
        return new self($loader, $globalVariables);
    }

    /**
     * @return array
     */
    public function load(): array
    {
        $loader = $this->loader;
        $config = $loader($this->globalVariables);
        foreach ($this->onPostLoad as $postLoader) {
            $config = $postLoader($config);
        }

        return $config;
    }

    public function addPostLoader(callable $postLoader): void
    {
        $this->onPostLoad[] = $postLoader;
    }
}
