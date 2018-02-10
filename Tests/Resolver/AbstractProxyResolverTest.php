<?php

namespace Overblog\GraphQLBundle\Tests\Resolver;

abstract class AbstractProxyResolverTest extends AbstractResolverTest
{
    protected function getResolverSolutionsMapping()
    {
        return [
            'Toto' => ['factory' => [[$this, 'createToto'], []], 'aliases' => ['foo', 'bar', 'baz'], 'method' => 'resolve'],
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
     * @param string $alias
     *
     * @dataProvider aliasProvider
     */
    public function testResolveAliasesMutation($alias)
    {
        $result = $this->resolver->resolve([$alias, ['my', 'resolve', 'test']]);
        $this->assertSame(
            $this->resolver->getSolution('Toto'),
            $this->resolver->getSolution($alias)
        );

        $this->assertEquals(['my', 'resolve', 'test'], $result);
    }

    /**
     * @expectedException \Overblog\GraphQLBundle\Resolver\UnresolvableException
     */
    public function testResolveUnknownMutation()
    {
        $this->resolver->resolve('Fake');
    }

    public function aliasProvider()
    {
        yield ['foo'];
        yield ['bar'];
        yield ['baz'];
    }
}
