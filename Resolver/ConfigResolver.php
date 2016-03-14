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

    public function __construct() {
        $solutionsMapping = [
            'fields' => [$this, 'resolveFields'],
            'isTypeOf' => [$this, 'resolveResolveCallback'],
            'interfaces' => [$this, 'resolveInterfaces'],
            'types' => [$this, 'resolveTypes'],
            'values' => [$this, 'resolveValues'],
            'resolveType' => [$this, 'resolveResolveCallback'],
            'resolveCursor' => [$this, 'resolveResolveCallback'],
            'resolveNode' => [$this, 'resolveResolveCallback'],
            'nodeType' => [$this, 'resolveTypeCallback'],
            'connectionFields' => [$this, 'resolveFields'],
            'edgeFields' => [$this, 'resolveFields'],
            'mutateAndGetPayload' => [$this, 'resolveResolveCallback'],
            'idFetcher' => [$this, 'resolveResolveCallback'],
            'nodeInterfaceType' => [$this, 'resolveTypeCallback'],
            'inputType' => [$this, 'resolveTypeCallback'],
            'outputType' => [$this, 'resolveTypeCallback'],
            'payloadType' => [$this, 'resolveTypeCallback'],
            'resolveSingleInput' => [$this, 'resolveResolveCallback'],
        ];

        foreach ($solutionsMapping as $name => $solution) {
            $this->addSolution($name, $solution);
        }

        parent::__construct();
    }

    public function setDefaultResolveFn(callable $defaultResolveFn)
    {
        Executor::setDefaultResolveFn($defaultResolveFn);

        $this->defaultResolveFn = $defaultResolveFn;
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
            $values = call_user_func_array($solution, [$values]);
        }

        return $config;
    }

    protected function supportedSolutionClass()
    {
        return 'Overblog\\GraphQLBundle\\Resolver\\Config\\ConfigSolutionInterface';
    }

}
