<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Resolver\Config;

use Overblog\GraphQLBundle\Resolver\Config\TypeConfigSolution;

/**
 * @property TypeConfigSolution $configSolution
 */
class TypeConfigSolutionTest extends AbstractConfigSolutionTest
{
    protected function createConfigSolution()
    {
        return new TypeConfigSolution();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid type! Must be instance of "GraphQL\Type\Definition\Type"
     */
    public function testSolveType()
    {
        $this->configSolution->solveType('toto');
    }
}
