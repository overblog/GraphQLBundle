<?php

namespace Overblog\GraphQLBundle\Definition\Builder;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Overblog\GraphQLBundle\Definition\Type\SchemaDecorator;
use Overblog\GraphQLBundle\Resolver\ResolverMapInterface;
use Overblog\GraphQLBundle\Resolver\ResolverMaps;
use Overblog\GraphQLBundle\Resolver\TypeResolver;

class SchemaBuilder
{
    /** @var TypeResolver */
    private $typeResolver;

    /** @var SchemaDecorator */
    private $decorator;

    /** @var bool */
    private $enableValidation;

    public function __construct(TypeResolver $typeResolver, SchemaDecorator $decorator, $enableValidation = false)
    {
        $this->typeResolver = $typeResolver;
        $this->decorator = $decorator;
        $this->enableValidation = $enableValidation;
    }

    /**
     * @param null|string            $queryAlias
     * @param null|string            $mutationAlias
     * @param null|string            $subscriptionAlias
     * @param ResolverMapInterface[] $resolverMaps
     *
     * @return Schema
     */
    public function create($queryAlias = null, $mutationAlias = null, $subscriptionAlias = null, array $resolverMaps = [])
    {
        $query = $this->typeResolver->resolve($queryAlias);
        $mutation = $this->typeResolver->resolve($mutationAlias);
        $subscription = $this->typeResolver->resolve($subscriptionAlias);

        $schema = new Schema($this->buildSchemaArguments($query, $mutation, $subscription));
        reset($resolverMaps);
        $this->decorator->decorate($schema, 1 === count($resolverMaps) ? current($resolverMaps) : new ResolverMaps($resolverMaps));

        if ($this->enableValidation) {
            $schema->assertValid();
        }

        return $schema;
    }

    private function buildSchemaArguments(Type $query = null, Type $mutation = null, Type $subscription = null)
    {
        return [
            'query' => $query,
            'mutation' => $mutation,
            'subscription' => $subscription,
            'typeLoader' => function ($name) {
                return $this->typeResolver->resolve($name);
            },
            'types' => function () {
                return $this->typeResolver->getSolutions();
            },
        ];
    }
}
