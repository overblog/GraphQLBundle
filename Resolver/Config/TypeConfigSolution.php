<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Resolver\Config;

class TypeConfigSolution extends AbstractConfigSolution
{
    const TYPE_CLASS = 'GraphQL\\Type\\Definition\\Type';
    const INTERFACE_CLASS = 'GraphQL\\Type\\Definition\\InterfaceType';

    public function solveTypeCallback($values)
    {
        return function () use ($values) {
            return $this->solveType($values);
        };
    }

    public function solveType($expr, $parentClass = self::TYPE_CLASS)
    {
        $type = $this->typeResolver->resolve($expr);

        if (null !== $parentClass && !$type instanceof $parentClass) {
            throw new \InvalidArgumentException(
                sprintf('Invalid type! Must be instance of "%s"', $parentClass)
            );
        }

        return $type;
    }

    public function solveTypes(array $rawTypes, $parentClass = self::TYPE_CLASS)
    {
        $types = [];

        foreach ($rawTypes as $alias) {
            $types[] = $this->solveType($alias, $parentClass);
        }

        return $types;
    }

    public function solveInterfaces(array $rawInterfaces)
    {
        return $this->solveTypes($rawInterfaces, self::INTERFACE_CLASS);
    }
}
