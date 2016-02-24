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
        switch ($type) {
            case 'relay-connection':
                $class = 'Overblog\\GraphQLBundle\\Relay\\Connection\\ConnectionType';
                break;

            case 'relay-node':
                $class = 'Overblog\\GraphQLBundle\\Relay\\Node\\NodeInterfaceType';
                break;

            case 'relay-mutation-input':
                $class = 'Overblog\\GraphQLBundle\\Relay\\Mutation\\InputType';
                break;

            case 'relay-mutation-payload':
                $class = 'Overblog\\GraphQLBundle\\Relay\\Mutation\\PayloadType';
                break;

            case 'object':
            case 'enum':
            case 'interface':
            case 'union':
                $class = sprintf('GraphQL\\Type\\Definition\\%sType', ucfirst($type));
                break;

            case 'input-object':
                $class = 'GraphQL\\Type\\Definition\\InputObjectType';
                break;

            default:
                throw new \RuntimeException(sprintf('Type "%s" is not managed.'), $type);
        }

        return $class;
    }
}
