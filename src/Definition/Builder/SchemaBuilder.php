<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\Builder;

use Closure;
use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\Type\ExtensibleSchema;
use Overblog\GraphQLBundle\Definition\Type\SchemaExtension\ValidatorExtension;
use Overblog\GraphQLBundle\Resolver\TypeResolver;

use Symfony\Contracts\Service\ResetInterface;
use function array_map;

final class SchemaBuilder implements ResetInterface
{
    private TypeResolver $typeResolver;
    private bool $enableValidation;
    private array $builders = [];

    public function __construct(TypeResolver $typeResolver, bool $enableValidation = false)
    {
        $this->typeResolver = $typeResolver;
        $this->enableValidation = $enableValidation;
    }

    public function getBuilder(string $name, ?string $queryAlias, string $mutationAlias = null, string $subscriptionAlias = null, array $types = [], bool $resettable = false): Closure
    {
        return function () use ($name, $queryAlias, $mutationAlias, $subscriptionAlias, $types, $resettable): ExtensibleSchema {
            if (!isset($this->builders[$name])) {
                $this->builders[$name] = $this->create($name, $queryAlias, $mutationAlias, $subscriptionAlias, $types, $resettable);
            }

            return $this->builders[$name];
        };
    }

    /**
     * @param string[] $types
     */
    public function create(string $name, ?string $queryAlias, string $mutationAlias = null, string $subscriptionAlias = null, array $types = [], bool $resettable = false): ExtensibleSchema
    {
        $this->typeResolver->setCurrentSchemaName($name);
        $query = $this->typeResolver->resolve($queryAlias);
        $mutation = $this->typeResolver->resolve($mutationAlias);
        $subscription = $this->typeResolver->resolve($subscriptionAlias);

        $schema = new ExtensibleSchema($this->buildSchemaArguments($name, $query, $mutation, $subscription, $types));
        $schema->setIsResettable($resettable);
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

    public function reset(): void
    {
        $this->builders = array_filter(
            $this->builders,
            fn (ExtensibleSchema $schema) => false === $schema->isResettable()
        );
    }
}
