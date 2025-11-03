<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Validator;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Type\GeneratedTypeInterface;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;

final class Utils
{
    /**
     * Restructures short forms into the full form array and
     * unwraps constraints in closures.
     *
     * @param mixed $config
     */
    public static function normalizeConfig($config): array
    {
        if ($config instanceof Closure) {
            return ['constraints' => $config()];
        }

        if (InputValidator::CASCADE === $config) {
            return ['cascade' => []];
        }

        if (isset($config['constraints']) && $config['constraints'] instanceof Closure) {
            $config['constraints'] = $config['constraints']();
        }

        return $config;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public static function unclosure($value)
    {
        if ($value instanceof Closure) {
            return $value();
        }

        return $value;
    }

    public static function isListOfType(GeneratedTypeInterface|ListOfType|NonNull $type): bool
    {
        if ($type instanceof ListOfType || ($type instanceof NonNull && $type->getWrappedType() instanceof ListOfType)) {
            return true;
        }

        return false;
    }

    /**
     * Since all GraphQL arguments and fields are represented by ValidationNode
     * objects, it is possible to define constraints at the class level.
     *
     * Class level constraints can be defined in three different ways:
     * - linked from an existing class/entity
     * - defined per field
     * - defined per type
     *
     * This method merges all of them into a single array and returns it.
     *
     * @link https://github.com/overblog/GraphQLBundle/blob/master/docs/validation/index.md#applying-of-validation-constraints
     */
    public static function getClassLevelConstraints(ResolveInfo $info): array
    {
        $typeLevel = Utils::normalizeConfig($info->parentType->config['validation'] ?? []);
        $fieldLevel = Utils::normalizeConfig($info->fieldDefinition->config['validation'] ?? []);

        return array_filter([
            'link' => $fieldLevel['link'] ?? $typeLevel['link'] ?? null,
            'constraints' => [
                ...($typeLevel['constraints'] ?? []),
                ...($fieldLevel['constraints'] ?? []),
            ],
        ]);
    }
}
