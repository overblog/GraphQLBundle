<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Resolver;

use GraphQL\Executor\Executor;

class ConfigResolver extends AbstractResolver
{
    /**
     * @var callable
     */
    private $defaultResolveFn = ['GraphQL\Executor\Executor', 'defaultResolveFn'];

    public function setDefaultResolveFn(callable $defaultResolveFn)
    {
        Executor::setDefaultResolveFn($defaultResolveFn);

        $this->defaultResolveFn = $defaultResolveFn;
    }

    public function getDefaultResolveFn()
    {
        return $this->defaultResolveFn;
    }

    public function resolve($config)
    {
        if (!is_array($config) || $config instanceof \ArrayAccess) {
            throw new \RuntimeException('Config must be an array or implement \ArrayAccess interface');
        }

        foreach ($config as $name => &$values) {
            if ((!$solution = $this->getSolution($name)) || empty($values)) {
                continue;
            }
            $options = $this->getSolutionOptions($name);

            $values = call_user_func_array([$solution, $options['method']], [$values]);
        }

        return $config;
    }

    protected function supportedSolutionClass()
    {
        return 'Overblog\\GraphQLBundle\\Resolver\\Config\\ConfigSolutionInterface';
    }
}
