<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Resolver;

interface ResolverInterface
{
    public function resolve($input);

    public function addSolution($name, callable $solutionFunc, array $solutionFuncArgs = [], array $options = []);

    public function getSolution($name);

    public function getSolutions();

    public function getSolutionOptions($name);
}
