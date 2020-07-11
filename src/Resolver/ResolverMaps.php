<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Resolver;

use InvalidArgumentException;
use function array_merge;
use function array_unique;
use function get_class;
use function gettype;
use function is_object;
use function sprintf;

final class ResolverMaps implements ResolverMapInterface
{
    private array $resolverMaps;

    public function __construct(array $resolverMaps)
    {
        self::checkResolverMaps($resolverMaps);
        $this->resolverMaps = $resolverMaps;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $typeName, string $fieldName)
    {
        foreach ($this->resolverMaps as $resolverMap) {
            if ($resolverMap->isResolvable($typeName, $fieldName)) {
                return $resolverMap->resolve($typeName, $fieldName);
            }
        }
        throw new UnresolvableException(sprintf('Field "%s.%s" could not be resolved.', $typeName, $fieldName));
    }

    /**
     * {@inheritdoc}
     */
    public function isResolvable(string $typeName, string $fieldName): bool
    {
        foreach ($this->resolverMaps as $resolverMap) {
            if ($resolverMap->isResolvable($typeName, $fieldName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function covered(?string $typeName = null)
    {
        $covered = [];
        foreach ($this->resolverMaps as $resolverMap) {
            $covered = array_merge($covered, $resolverMap->covered($typeName));
        }
        $covered = array_unique($covered);

        return $covered;
    }

    private static function checkResolverMaps(array $resolverMaps): void
    {
        foreach ($resolverMaps as $resolverMap) {
            if (!$resolverMap instanceof ResolverMapInterface) {
                throw new InvalidArgumentException(sprintf(
                    'ResolverMap should be instance of "%s" but got "%s".',
                    ResolverMapInterface::class,
                    is_object($resolverMap) ? get_class($resolverMap) : gettype($resolverMap)
                ));
            }
        }
    }
}
