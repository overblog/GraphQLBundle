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

use Overblog\GraphQLBundle\Resolver\Config\ValuesConfigSolution;

/**
 * @property ValuesConfigSolution $configSolution
 */
class ValuesConfigSolutionTest extends AbstractConfigSolutionTest
{
    protected function createConfigSolution()
    {
        return new ValuesConfigSolution();
    }

    public function testSolve()
    {
        $config = $this->configSolution->solve(
            [
                'values' => [
                    'test' => ['value' => 'my test value'],
                    'toto' => ['value' => 'my toto value'],
                    'expression-language-test' => ['value' => '@=["my", "test"]'],
                ],
            ]
        );

        $expected = [
            'values' => [
                'test' => ['value' => 'my test value'],
                'toto' => ['value' => 'my toto value'],
                'expression-language-test' => ['value' => ['my', 'test']],
            ],
        ];

        $this->assertEquals($expected, $config);
    }
}
