<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Resolver;

use function array_keys;

abstract class AbstractResolver implements FluentResolverInterface
{
    private array $solutions = [];
    private array $aliases = [];
    private array $solutionOptions = [];
    private array $fullyLoadedSolutions = [];

    public function addSolution(string $id, callable $factory, array $aliases = [], array $options = []): self
    {
        $this->fullyLoadedSolutions[$id] = false;
        $this->addAliases($id, $aliases);

        $this->solutions[$id] = $factory;
        $this->solutionOptions[$id] = $options;

        return $this;
    }

    public function hasSolution(string $id): bool
    {
        $id = $this->resolveAlias($id);

        return isset($this->solutions[$id]);
    }

    /**
     * @return mixed
     */
    public function getSolution(string $id)
    {
        return $this->loadSolution($id);
    }

    public function getSolutions(): array
    {
        return $this->loadSolutions();
    }

    public function getSolutionAliases(string $id): array
    {
        return array_keys($this->aliases, $id);
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * @return mixed
     */
    public function getSolutionOptions(string $id)
    {
        $id = $this->resolveAlias($id);

        return $this->solutionOptions[$id] ?? [];
    }

    /**
     * @param mixed $solution
     */
    protected function onLoadSolution($solution): void
    {
    }

    /**
     * @return mixed
     */
    private function loadSolution(string $id)
    {
        $id = $this->resolveAlias($id);
        if (!$this->hasSolution($id)) {
            return null;
        }

        if ($this->fullyLoadedSolutions[$id]) {
            return $this->solutions[$id];
        }
        $loader = $this->solutions[$id];
        $this->solutions[$id] = $solution = $loader();
        $this->onLoadSolution($solution);
        $this->fullyLoadedSolutions[$id] = true;

        return $solution;
    }

    private function addAliases(string $id, array $aliases): void
    {
        foreach ($aliases as $alias) {
            $this->aliases[$alias] = $id;
        }
    }

    private function resolveAlias(string $alias): string
    {
        return $this->aliases[$alias] ?? $alias;
    }

    /**
     * @return mixed[]
     */
    private function loadSolutions(): array
    {
        foreach ($this->solutions as $name => &$solution) {
            $solution = $this->loadSolution($name);
        }

        return $this->solutions;
    }
}
