<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\DependencyInjection\Configurator;

use Overblog\GraphQLBundle\Resolver\AbstractResolver;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ResolverConfigurator implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private $mappings;

    public function addMapping($name, array $mapping = [])
    {
        $this->mappings[$name] = $mapping;
    }

    private function configure(AbstractResolver $resolver, array $mapping)
    {
        foreach ($mapping as $name => $options) {
            $cleanOptions = $options;
            unset($cleanOptions['alias'], $cleanOptions['id']);

            $solution = $this->container->get($options['id']);

            if ($solution instanceof ContainerAwareInterface) {
                $solution->setContainer($this->container);
            }

            $resolver->addSolution($name, $solution, $cleanOptions);
        }
    }

    public function __call($name, $arguments)
    {
        if (!preg_match('/^configure(.*)/i', $name, $matches) || !isset($this->mappings[strtolower($matches[1])])) {
            throw new \BadMethodCallException(sprintf('Call to unknown method %s', $name));
        }

        $mapping = $this->mappings[strtolower($matches[1])];

        if (!isset($arguments[0]) || !$arguments[0] instanceof AbstractResolver) {
            throw new \InvalidArgumentException(
                sprintf('Resolver must implement "%s"', 'Overblog\\GraphQLBundle\\Resolver\\AbstractResolver')
            );
        }

        $this->configure($arguments[0], $mapping);
    }
}
