<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Resolver;

use Generator;
use Overblog\GraphQLBundle\Resolver\UnresolvableException;

abstract class AbstractProxyResolverTest extends AbstractResolverTest
{
    protected function getResolverSolutionsMapping(): array
    {
        return [
            'Toto' => ['factory' => [[$this, 'createToto'], []], 'aliases' => ['foo', 'bar', 'baz'], 'method' => 'resolve'],
        ];
    }

    public function createToto(): Toto
    {
        return new Toto();
    }

    public function testResolveKnownMutation(): void
    {
        $result = $this->resolver->resolve(['Toto', ['my', 'resolve', 'test']]);

        $this->assertSame(['my', 'resolve', 'test'], $result);
    }

    /**
     * @param string $alias
     *
     * @dataProvider aliasProvider
     */
    public function testResolveAliasesMutation($alias): void
    {
        $result = $this->resolver->resolve([$alias, ['my', 'resolve', 'test']]);
        $this->assertSame(
            $this->resolver->getSolution('Toto'),
            $this->resolver->getSolution($alias)
        );

        $this->assertSame(['my', 'resolve', 'test'], $result);
    }

    public function testResolveUnknownMutation(): void
    {
        $this->expectException(UnresolvableException::class);
        $this->resolver->resolve('Fake');
    }

    public function aliasProvider(): Generator
    {
        yield ['foo'];
        yield ['bar'];
        yield ['baz'];
    }
}
