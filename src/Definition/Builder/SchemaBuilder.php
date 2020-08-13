<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\Builder;

use Closure;
use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\Type\ExtensibleSchema;
use Overblog\GraphQLBundle\Definition\Type\SchemaExtension\ValidatorExtension;
use Overblog\GraphQLBundle\Resolver\TypeResolver;
use function array_map;

class SchemaBuilder
{
    private TypeResolver $typeResolver;
    private bool $enableValidation;

    public function __construct(TypeResolver $typeResolver, bool $enableValidation = false)
    {
        $this->typeResolver = $typeResolver;
        $this->enableValidation = $enableValidation;
    }

    public function getBuilder(string $name, ?string $queryAlias, ?string $mutationAlias = null, ?string $subscriptionAlias = null, array $types = []): Closure
    {
        return function () use ($name, $queryAlias, $mutationAlias, $subscriptionAlias, $types): ExtensibleSchema {
            static $schema = null;
            if (null === $schema) {
                $schema = $this->create($name, $queryAlias, $mutationAlias, $subscriptionAlias, $types);
            }

            return $schema;
        };
    }

    /**
     * @param string[] $types
     */
    public function create(string $name, ?string $queryAlias, ?string $mutationAlias = null, ?string $subscriptionAlias = null, array $types = []): ExtensibleSchema
    {
        $this->typeResolver->setCurrentSchemaName($name);
        $query = $this->typeResolver->resolve($queryAlias);
        $mutation = $this->typeResolver->resolve($mutationAlias);
        $subscription = $this->typeResolver->resolve($subscriptionAlias);

        $schema = new ExtensibleSchema($this->buildSchemaArguments($name, $query, $mutation, $subscription, $types));
        $extensions = [];

        if ($this->enableValidation) {
            $extensions[] = new ValidatorExtension();
        }
        $schema->setExtensions($extensions);

        return $schema;
    }

    private function buildSchemaArguments(string $schemaName, Type $query, ?Type $mutation, ?Type $subscription, array $types = []): array
    {
        return [
            'query' => $query,
            'mutation' => $mutation,
            'subscription' => $subscription,
            'typeLoader' => function ($name) use ($schemaName) {
                $this->typeResolver->setCurrentSchemaName($schemaName);

                return $this->typeResolver->resolve($name);
            },
            'types' => function () use ($types, $schemaName) {
                $this->typeResolver->setCurrentSchemaName($schemaName);

                return array_map([$this->typeResolver, 'resolve'], $types);
            },
        ];
    }
}
