<?php

namespace Overblog\GraphQLBundle\Tests\Resolver;

use Overblog\GraphQLBundle\Resolver\AbstractResolver;
use Symfony\Component\EventDispatcher\EventDispatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class AbstractResolverTest extends TestCase
{
    /** @var AbstractResolver */
    protected $resolver;

    abstract protected function createResolver(EventDispatcherInterface $eventDispatcher);

    abstract protected function getResolverSolutionsMapping();

    public function setUp()
    {
        $eventDispatcher = new EventDispatcher();
        $this->resolver = $this->createResolver($eventDispatcher);

        foreach ($this->getResolverSolutionsMapping() as $name => $options) {
            $this->resolver->addSolution($name, $options['solutionFunc'], $options['solutionFuncArgs'], $options);
        }
    }
}
