<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Resolver\MutationResolver;
use Overblog\GraphQLBundle\Resolver\QueryResolver;
use Overblog\GraphQLBundle\Resolver\TypeResolver;
use Overblog\GraphQLBundle\Validator\InputValidator;
use Overblog\GraphQLBundle\Validator\InputValidatorFactory;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Container for special services to be passed to all generated types.
 */
final class GraphQLServices extends ServiceLocator
{
    /**
     * @param mixed ...$args
     *
     * @return mixed
     */
    public function query(string $alias, ...$args)
    {
        return $this->get(QueryResolver::class)->resolve([$alias, $args]);
    }

    /**
     * @param mixed ...$args
     *
     * @return mixed
     */
    public function mutation(string $alias, ...$args)
    {
        return $this->get(MutationResolver::class)->resolve([$alias, $args]);
    }

    /**
     * @phpstan-template T of Type
     * @phpstan-param class-string<T> $typeName
     * @phpstan-return ?T
     */
    public function getType(string $typeName): ?Type
    {
        return $this->get(TypeResolver::class)->resolve($typeName);
    }

    /**
     * Creates an instance of InputValidator
     *
     * @param mixed $value
     * @param mixed $context
     */
    public function createInputValidator($value, ArgumentInterface $args, $context, ResolveInfo $info): InputValidator
    {
        return $this->get(InputValidatorFactory::class)->create(
            new ResolverArgs($value, $args, $context, $info)
        );
    }
}
