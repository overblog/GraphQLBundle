<?php

namespace Overblog\GraphQLBundle\Resolver;

interface FluentResolverInterface
{
    public function resolve($input);

    public function addSolution($name, callable $solutionFunc, array $solutionFuncArgs = [], array $options = []);

    public function getSolution($name);

    public function getSolutions();

    public function getSolutionOptions($name);
}
