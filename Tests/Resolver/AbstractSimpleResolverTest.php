<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Resolver;

abstract class AbstractSimpleResolverTest extends AbstractResolverTest
{
    protected function getResolverSolutionsMapping()
    {
        $totoSolution = $this->getMock('Overblog\GraphQLBundle\Definition\Builder\MappingInterface');
        $totoSolution->name = 'Toto';

        return [
            'Toto' => ['solution' => $totoSolution],
        ];
    }

    public function testResolveKnownArg()
    {
        $arg = $this->resolver->resolve('Toto');

        $this->assertInstanceOf('Overblog\GraphQLBundle\Definition\Builder\MappingInterface', $arg);
        $this->assertEquals('Toto', $arg->name);
    }

    /**
     * @expectedException \Overblog\GraphQLBundle\Resolver\UnresolvableException
     */
    public function testResolveUnknownArg()
    {
        $this->resolver->resolve('Fake');
    }
}
