<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition;

use GraphQL\Type\Definition\Type;
use LogicException;
use Overblog\GraphQLBundle\Resolver\MutationResolver;
use Overblog\GraphQLBundle\Resolver\QueryResolver;
use Overblog\GraphQLBundle\Resolver\TypeResolver;
use ReflectionException;
use ReflectionMethod;

/**
 * Container for special services to be passed to all generated types.
 */
final class GraphQLServices
{
    private array $services;
    private TypeResolver $types;
    private QueryResolver $queryResolver;
    private MutationResolver $mutationResolver;

    public function __construct(
        TypeResolver $typeResolver,
        QueryResolver $queryResolver,
        MutationResolver $mutationResolver,
        array $services = []
    ) {
        $this->types = $typeResolver;
        $this->queryResolver = $queryResolver;
        $this->mutationResolver = $mutationResolver;
        $this->services = $services;
    }

    /**
     * @return mixed
     */
    public function get(string $name)
    {
        if (!isset($this->services[$name])) {
            throw new LogicException(sprintf('GraphQL service "%s" could not be located. You should define it.', $name));
        }

        return $this->services[$name];
    }

    /**
     * Get all GraphQL services.
     */
    public function getAll(): array
    {
        return $this->services;
    }

    public function has(string $name): bool
    {
        return isset($this->services[$name]);
    }

    /**
     * @param mixed ...$args
     *
     * @return mixed
     */
    public function query(string $alias, ...$args)
    {
        return $this->queryResolver->resolve([$alias, $args]);
    }

    /**
     * @param mixed ...$args
     *
     * @return mixed
     * @throws ReflectionException
     */
    public function mutation(string $alias, ...$args)
    {
        // TODO: remove the following if-block in 1.0
        if (1 === count($args) && is_array($args[0])) {
            $aliases = $this->mutationResolver->getAliases();

            if (isset($aliases[$alias])) {
                // Check if proxy resolver has the same amount of params
                $numOfParams = (new ReflectionMethod($aliases[$alias]))->getNumberOfParameters();
                if (count($args[0]) === $numOfParams) {
                    $args = $args[0];
                }
            }
        }

        return $this->mutationResolver->resolve([$alias, $args]);
    }

    public function getType(string $typeName): ?Type
    {
        return $this->types->resolve($typeName);
    }
}
