<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Definition\Builder;

use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Resolver\ResolverInterface;

class TypeBuilder
{
    private $configResolver;

    private $mapping = [
        'relay-connection' => 'Overblog\\GraphQLBundle\\Relay\\Connection\\ConnectionType',
        'relay-node' => 'Overblog\\GraphQLBundle\\Relay\\Node\\NodeInterfaceType',
        'relay-mutation-input' => 'Overblog\\GraphQLBundle\\Relay\\Mutation\\InputType',
        'relay-mutation-payload' => 'Overblog\\GraphQLBundle\\Relay\\Mutation\\PayloadType',
        'object' => 'GraphQL\\Type\\Definition\\ObjectType',
        'enum' => 'GraphQL\\Type\\Definition\\EnumType',
        'interface' => 'GraphQL\\Type\\Definition\\InterfaceType',
        'union' => 'GraphQL\\Type\\Definition\\UnionType',
        'input-object' => 'GraphQL\\Type\\Definition\\InputObjectType',
    ];

    public function __construct(ResolverInterface $configResolver)
    {
        $this->configResolver = $configResolver;
    }

    /**
     * @param $type
     * @param array $config
     *
     * @return Type
     */
    public function create($type, array $config)
    {
        $class = $this->getBaseClassName($type);

        return new $class($this->configResolver->resolve($config));
    }

    private function getBaseClassName($type)
    {
        if (!isset($this->mapping[$type])) {
            throw new \RuntimeException(sprintf('Type "%s" is not managed.', $type));
        }

        return $this->mapping[$type];
    }
}
