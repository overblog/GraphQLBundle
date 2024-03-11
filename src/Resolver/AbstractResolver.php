<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Resolver;

use Symfony\Contracts\Service\ResetInterface;
use function array_keys;

abstract class AbstractResolver implements FluentResolverInterface, ResetInterface
{
    private array $solutionsFactory = [];
    private array $solutions = [];
    private array $aliases = [];
    private array $solutionOptions = [];

    public function addSolution(string $id, callable $factory, array $aliases = [], array $options = []): self
    {
        $this->addAliases($id, $aliases);

        $this->solutionsFactory[$id] = $factory;
        $this->solutionOptions[$id] = $options;

        return $this;
    }

    public function hasSolution(string $id): bool
    {
        $id = $this->resolveAlias($id);

        return isset($this->solutionsFactory[$id]);
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

        if (isset($this->solutions[$id])) {
            return $this->solutions[$id];
        } else {
            $loader = $this->solutionsFactory[$id];
            $this->solutions[$id] = $solution = $loader();
            $this->onLoadSolution($solution);

            return $solution;
        }
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
        foreach (array_keys($this->solutionsFactory) as $name) {
            $this->loadSolution($name);
        }

        return $this->solutions;
    }

    public function reset(): void
    {
        $this->solutions = [];
    }
}
