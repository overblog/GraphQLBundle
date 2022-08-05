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
    protected TypeResolver $typeResolver;
    protected bool $enableValidation;

    public function __construct(TypeResolver $typeResolver, bool $enableValidation = false)
    {
        $this->typeResolver = $typeResolver;
        $this->enableValidation = $enableValidation;
    }

    /**
     * @param string[] $types
     */
    public function getBuilder(
        string $name,
        ?string $queryAlias,
        ?string $mutationAlias = null,
        ?string $subscriptionAlias = null,
        array $types = []
    ): Closure {
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
    public function create(
        string $name,
        ?string $queryAlias,
        ?string $mutationAlias = null,
        ?string $subscriptionAlias = null,
        array $types = []
    ): ExtensibleSchema {
        $this->typeResolver->setCurrentSchemaName($name);
        $query = $this->typeResolver->resolve($queryAlias);
        $mutation = $this->typeResolver->resolve($mutationAlias);
        $subscription = $this->typeResolver->resolve($subscriptionAlias);

        /** @var class-string<ExtensibleSchema> $class */
        $class = $this->getSchemaClass();

        $schema = new $class($this->buildSchemaArguments($name, $query, $mutation, $subscription, $types));
        $extensions = [];

        if ($this->enableValidation) {
            $extensions[] = new ValidatorExtension();
        }
        $schema->setExtensions($extensions);

        return $schema;
    }

    /**
     * @param string[] $types
     *
     * @return array<string,mixed>
     */
    protected function buildSchemaArguments(
        string $schemaName,
        ?Type $query,
        ?Type $mutation,
        ?Type $subscription,
        array $types = []
    ): array {
        return [
            'query' => $query,
            'mutation' => $mutation,
            'subscription' => $subscription,
            'typeLoader' => $this->createTypeLoaderClosure($schemaName),
            'types' => $this->createTypesClosure($schemaName, $types),
        ];
    }

    protected function createTypeLoaderClosure(string $schemaName): Closure
    {
        return function ($name) use ($schemaName): ?Type {
            $this->typeResolver->setCurrentSchemaName($schemaName);

            return $this->typeResolver->resolve($name);
        };
    }

    /**
     * @param string[] $types
     */
    protected function createTypesClosure(string $schemaName, array $types): Closure
    {
        return function () use ($types, $schemaName): array {
            $this->typeResolver->setCurrentSchemaName($schemaName);

            return array_map(fn (string $x): ?Type => $this->typeResolver->resolve($x), $types);
        };
    }

    /**
     * @return class-string<ExtensibleSchema>
     */
    protected function getSchemaClass(): string
    {
        return ExtensibleSchema::class;
    }
}
