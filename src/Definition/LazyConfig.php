<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition;

use Closure;

final class LazyConfig
{
    private Closure $loader;

    /**
     * @var callable[]
     */
    private array $onPostLoad = [];

    private function __construct(Closure $loader)
    {
        $this->loader = $loader;
    }

    public static function create(Closure $loader): self
    {
        return new self($loader);
    }

    public function load(): array
    {
        $config = ($this->loader)();

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
