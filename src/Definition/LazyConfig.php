<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition;

use Closure;

final class LazyConfig
{
    private Closure $loader;
    private ?GlobalVariables $globalVariables;

    /**
     * @var callable[]
     */
    private array $onPostLoad = [];

    private function __construct(Closure $loader, GlobalVariables $globalVariables = null)
    {
        $this->loader = $loader;
        $this->globalVariables = $globalVariables ?: new GlobalVariables();
    }

    public static function create(Closure $loader, GlobalVariables $globalVariables = null): self
    {
        return new self($loader, $globalVariables);
    }

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
