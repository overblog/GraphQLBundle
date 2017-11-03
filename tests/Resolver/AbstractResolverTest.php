<?php

namespace Overblog\GraphQLBundle\Tests\Resolver;

use Overblog\GraphQLBundle\Resolver\AbstractResolver;
use PHPUnit\Framework\TestCase;

abstract class AbstractResolverTest extends TestCase
{
    /** @var AbstractResolver */
    protected $resolver;

    abstract protected function createResolver();

    abstract protected function getResolverSolutionsMapping();

    public function setUp()
    {
        $this->resolver = $this->createResolver();

        foreach ($this->getResolverSolutionsMapping() as $name => $options) {
            $this->resolver->addSolution($name, $options['solutionFunc'], $options['solutionFuncArgs'], $options);
        }
    }
}
