<?php

namespace Overblog\GraphQLBundle\Definition\Builder;

use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\Type\ExtensibleSchema;
use Overblog\GraphQLBundle\Definition\Type\SchemaExtension\DecoratorExtension;
use Overblog\GraphQLBundle\Definition\Type\SchemaExtension\ValidatorExtension;
use Overblog\GraphQLBundle\Resolver\ResolverMapInterface;
use Overblog\GraphQLBundle\Resolver\ResolverMaps;
use Overblog\GraphQLBundle\Resolver\TypeResolver;

class SchemaBuilder
{
    /** @var TypeResolver */
    private $typeResolver;

    /** @var bool */
    private $enableValidation;

    public function __construct(TypeResolver $typeResolver, $enableValidation = false)
    {
        $this->typeResolver = $typeResolver;
        $this->enableValidation = $enableValidation;
    }

    /**
     * @param string|null            $queryAlias
     * @param string|null            $mutationAlias
     * @param string|null            $subscriptionAlias
     * @param ResolverMapInterface[] $resolverMaps
     * @param string[]               $types
     *
     * @return ExtensibleSchema
     */
    public function create($queryAlias = null, $mutationAlias = null, $subscriptionAlias = null, array $resolverMaps = [], array $types = [])
    {
        $query = $this->typeResolver->resolve($queryAlias);
        $mutation = $this->typeResolver->resolve($mutationAlias);
        $subscription = $this->typeResolver->resolve($subscriptionAlias);
        $schema = new ExtensibleSchema($this->buildSchemaArguments($query, $mutation, $subscription, $types));
        $extensions = [new DecoratorExtension(1 === \count($resolverMaps) ? \current($resolverMaps) : new ResolverMaps($resolverMaps))];

        if ($this->enableValidation) {
            $extensions[] = new ValidatorExtension();
        }
        $schema->setExtensions($extensions);

        return $schema;
    }

    private function buildSchemaArguments(Type $query = null, Type $mutation = null, Type $subscription = null, array $types = [])
    {
        return [
            'query' => $query,
            'mutation' => $mutation,
            'subscription' => $subscription,
            'typeLoader' => function ($name) {
                return $this->typeResolver->resolve($name);
            },
            'types' => function () use ($types) {
                return \array_map([$this->typeResolver, 'getSolution'], $types);
            },
        ];
    }
}
