<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Processor;

use Exception;
use InvalidArgumentException;
use function array_column;
use function array_filter;
use function array_flip;
use function array_intersect_key;
use function array_keys;
use function array_merge;
use function array_replace_recursive;
use function array_search;
use function array_unique;
use function array_values;
use function implode;
use function in_array;
use function is_array;
use function json_encode;
use function sprintf;
use function uksort;

final class InheritanceProcessor implements ProcessorInterface
{
    public const HEIRS_KEY = 'heirs';
    public const INHERITS_KEY = 'inherits';

    public static function process(array $configs): array
    {
        $configs = self::processConfigsHeirs($configs);
        $configs = self::processConfigsInherits($configs);
        $configs = self::removedDecorators($configs);

        return $configs;
    }

    private static function removedDecorators(array $configs): array
    {
        return array_filter($configs, function ($config) {
            return !isset($config['decorator']) || true !== $config['decorator'];
        });
    }

    private static function processConfigsHeirs(array $configs): array
    {
        foreach ($configs as $parentName => &$config) {
            if (!empty($config[self::HEIRS_KEY])) {
                // allows shorthand configuration `heirs: QueryFooDecorator` is equivalent to `heirs: [QueryFooDecorator]`
                if (!is_array($config[self::HEIRS_KEY])) {
                    $config[self::HEIRS_KEY] = [$config[self::HEIRS_KEY]];
                }
                foreach ($config[self::HEIRS_KEY] as $heirs) {
                    if (!isset($configs[$heirs])) {
                        throw new InvalidArgumentException(sprintf(
                            'Type %s child of %s not found.',
                            json_encode($heirs),
                            json_encode($parentName)
                        ));
                    }

                    if (!isset($configs[$heirs][self::INHERITS_KEY])) {
                        $configs[$heirs][self::INHERITS_KEY] = [];
                    }

                    $configs[$heirs][self::INHERITS_KEY][] = $parentName;
                }
            }
            unset($config[self::HEIRS_KEY]);
        }

        return $configs;
    }

    private static function processConfigsInherits(array $configs): array
    {
        foreach ($configs as $name => &$config) {
            if (!isset($config['type'])) {
                continue;
            }

            $allowedTypes = [$config['type']];
            if ('object' === $config['type']) {
                $allowedTypes[] = 'interface';
            }
            $flattenInherits = self::flattenInherits($name, $configs, $allowedTypes);
            if (empty($flattenInherits)) {
                continue;
            }
            $config = self::inheritsTypeConfig($name, $flattenInherits, $configs);
        }

        return $configs;
    }

    /**
     * @throws Exception
     */
    private static function inheritsTypeConfig(string $child, array $parents, array $configs): array
    {
        $parentTypes = array_intersect_key($configs, array_flip($parents));

        // Restore initial order
        uksort($parentTypes, fn ($a, $b) => (int) (array_search($a, $parents, true) > array_search($b, $parents, true)));

        $mergedParentsConfig = self::mergeConfigs(...array_column($parentTypes, 'config'));
        $childType = $configs[$child];
        // unset resolveType field resulting from the merge of a "interface" type
        if ('object' === $childType['type']) {
            unset($mergedParentsConfig['resolveType']);
        }

        if (isset($mergedParentsConfig['interfaces'], $childType['config']['interfaces'])) {
            $childType['config']['interfaces'] = array_merge($mergedParentsConfig['interfaces'], $childType['config']['interfaces']);
        }

        $configs = array_replace_recursive(['config' => $mergedParentsConfig], $childType);

        return $configs;
    }

    private static function flattenInherits(
        string $name,
        array $configs,
        array $allowedTypes,
        string $child = null,
        array $typesTreated = []
    ): array {
        self::checkTypeExists($name, $configs, $child);
        self::checkCircularReferenceInheritsTypes($name, $typesTreated);
        self::checkAllowedInheritsTypes($name, $configs[$name], $allowedTypes, $child);

        // flatten
        $config = $configs[$name];
        if (empty($config[self::INHERITS_KEY]) || !is_array($config[self::INHERITS_KEY])) {
            return [];
        }
        $typesTreated[$name] = true;
        $flattenInheritsTypes = [];
        foreach ($config[self::INHERITS_KEY] as $typeToInherit) {
            $flattenInheritsTypes = array_merge(
                $flattenInheritsTypes,
                self::flattenInherits($typeToInherit, $configs, $allowedTypes, $name, $typesTreated)
            );
            $flattenInheritsTypes[] = $typeToInherit;
        }

        return $flattenInheritsTypes;
    }

    private static function checkTypeExists(string $name, array $configs, ?string $child): void
    {
        if (!isset($configs[$name])) {
            throw new InvalidArgumentException(sprintf(
                'Type %s inherited by %s not found.',
                json_encode($name),
                json_encode($child)
            ));
        }
    }

    private static function checkCircularReferenceInheritsTypes(string $name, array $typesTreated): void
    {
        if (isset($typesTreated[$name])) {
            throw new InvalidArgumentException(sprintf(
                'Type circular inheritance detected (%s).',
                implode('->', array_merge(array_keys($typesTreated), [$name]))
            ));
        }
    }

    private static function checkAllowedInheritsTypes(string $name, array $config, array $allowedTypes, ?string $child): void
    {
        if (empty($config['decorator']) && isset($config['type']) && !in_array($config['type'], $allowedTypes)) {
            throw new InvalidArgumentException(sprintf(
                'Type %s can\'t inherit %s because its type (%s) is not allowed type (%s).',
                json_encode($child),
                json_encode($name),
                json_encode($config['type']),
                json_encode($allowedTypes)
            ));
        }
    }

    private static function mergeConfigs(array ...$configs): array
    {
        $result = [];

        foreach ($configs as $config) {
            $interfaces = $result['interfaces'] ?? null;
            $result = array_replace_recursive($result, $config);

            if (!empty($interfaces) && !empty($config['interfaces'])) {
                $result['interfaces'] = array_values(array_unique(array_merge($interfaces, $config['interfaces'])));
            }
        }

        return $result;
    }
}
