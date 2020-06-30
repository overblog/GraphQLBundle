<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Resolver;

use GraphQL\Type\Definition\Type;
use function array_keys;
use function call_user_func_array;
use function get_class;
use function is_array;
use function is_callable;
use function sprintf;

abstract class AbstractResolver implements FluentResolverInterface
{
    private array $solutions = [];
    private array $aliases = [];
    private array $solutionOptions = [];
    private array $fullyLoadedSolutions = [];

    public function addSolution(string $id, $solutionOrFactory, array $aliases = [], array $options = []): self
    {
        $this->fullyLoadedSolutions[$id] = false;
        $this->addAliases($id, $aliases);

        $this->solutions[$id] = function () use ($id, $solutionOrFactory) {
            $solution = $solutionOrFactory;
            if (self::isSolutionFactory($solutionOrFactory)) {
                if (!isset($solutionOrFactory[1])) {
                    $solutionOrFactory[1] = [];
                }
                $solution = call_user_func_array(...$solutionOrFactory);
            }
            $this->checkSolution($id, $solution);

            return $solution;
        };
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

    /**
     * @return mixed
     */
    public function getSolutionOptions(string $id)
    {
        $id = $this->resolveAlias($id);

        return isset($this->solutionOptions[$id]) ? $this->solutionOptions[$id] : [];
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
        } else {
            $loader = $this->solutions[$id];
            $this->solutions[$id] = $solution = $loader();
            $this->onLoadSolution($solution);
            $this->fullyLoadedSolutions[$id] = true;

            return $solution;
        }
    }

    private function addAliases(string $id, array $aliases): void
    {
        foreach ($aliases as $alias) {
            $this->aliases[$alias] = $id;
        }
    }

    /**
     * @param object|array $solutionOrFactory
     */
    private static function isSolutionFactory($solutionOrFactory): bool
    {
        return is_array($solutionOrFactory) && isset($solutionOrFactory[0]) && is_callable($solutionOrFactory[0]);
    }

    private function resolveAlias(string $alias): string
    {
        return isset($this->aliases[$alias]) ? $this->aliases[$alias] : $alias;
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

    /**
     * @param mixed $solution
     */
    protected function supportsSolution($solution): bool
    {
        $supportedClass = $this->supportedSolutionClass();

        return  null === $supportedClass || $solution instanceof $supportedClass;
    }

    /**
     * @param mixed $solution
     */
    protected function checkSolution(string $id, $solution): void
    {
        if (!$this->supportsSolution($solution)) {
            throw new UnsupportedResolverException(
                sprintf('Resolver "%s" must be "%s" "%s" given.', $id, $this->supportedSolutionClass(), get_class($solution))
            );
        }
    }

    /**
     * default return null to accept mixed type.
     *
     * @return string|null supported class name
     */
    protected function supportedSolutionClass(): ?string
    {
        return null;
    }
}
