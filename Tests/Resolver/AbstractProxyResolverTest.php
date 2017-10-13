<?php

namespace Overblog\GraphQLBundle\Tests\Resolver;

abstract class AbstractProxyResolverTest extends AbstractResolverTest
{
    protected function getResolverSolutionsMapping()
    {
        return [
            'Toto' => ['solutionFunc' => [$this, 'createToto'], 'solutionFuncArgs' => [],  'method' => 'resolve'],
        ];
    }

    public function createToto()
    {
        return new Toto();
    }

    public function testResolveKnownMutation()
    {
        $result = $this->resolver->resolve(['Toto', ['my', 'resolve', 'test']]);

        $this->assertEquals(['my', 'resolve', 'test'], $result);
    }

    /**
     * @expectedException \Overblog\GraphQLBundle\Resolver\UnresolvableException
     */
    public function testResolveUnknownMutation()
    {
        $this->resolver->resolve('Fake');
    }
}
