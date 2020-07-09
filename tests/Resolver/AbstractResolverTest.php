<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Resolver;

use Overblog\GraphQLBundle\Resolver\AbstractResolver;
use PHPUnit\Framework\TestCase;

abstract class AbstractResolverTest extends TestCase
{
    protected AbstractResolver $resolver;

    abstract protected function createResolver(): AbstractResolver;

    abstract protected function getResolverSolutionsMapping(): array;

    public function setUp(): void
    {
        $this->resolver = $this->createResolver();

        foreach ($this->getResolverSolutionsMapping() as $name => $options) {
            $this->resolver->addSolution($name, $options['factory'], $options['aliases'] ?? [], $options);
        }
    }
}
