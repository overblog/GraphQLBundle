<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Resolver;

interface FluentResolverInterface
{
    /**
     * @param mixed $input
     *
     * @return mixed
     */
    public function resolve($input);

    /**
     * Add a solution to resolver.
     *
     * @param string   $id      the solution identifier
     * @param callable $factory the solution factory
     * @param string[] $aliases the solution aliases
     * @param array    $options extra options
     *
     * @return $this
     */
    public function addSolution(string $id, callable $factory, array $aliases = [], array $options = []): self;

    /**
     * @return mixed
     */
    public function getSolution(string $id);

    /**
     * @return mixed
     */
    public function getSolutions();

    /**
     * @return mixed
     */
    public function getSolutionAliases(string $id);

    /**
     * @return mixed
     */
    public function getSolutionOptions(string $id);
}
