<?php

namespace Overblog\GraphQLBundle\Resolver;

interface FluentResolverInterface
{
    public function resolve($input);

    /**
     * Add a solution to resolver.
     *
     * @param string      $id                the solution identifier
     * @param array|mixed $solutionOrFactory the solution itself or array with a factory and it arguments if needed [$factory] or [$factory, $factoryArgs]
     * @param string[]    $aliases           the solution aliases
     * @param array       $options           extra options
     *
     * @return $this
     */
    public function addSolution($id, $solutionOrFactory, array $aliases = [], array $options = []);

    public function getSolution($id);

    public function getSolutions();

    public function getSolutionAliases($id);

    public function getSolutionOptions($id);
}
