<?php

namespace Overblog\GraphQLBundle\Config\Processor;

final class InheritanceProcessor implements ProcessorInterface
{
    const HEIRS_KEY = 'heirs';
    const INHERITS_KEY = 'inherits';

    /**
     * {@inheritdoc}
     */
    public static function process(array $configs)
    {
        $configs = self::processConfigsHeirs($configs);
        $configs = self::processConfigsInherits($configs);
        $configs = self::removedDecorators($configs);

        return $configs;
    }

    private static function removedDecorators(array $configs)
    {
        return \array_filter($configs, function ($config) {
            return !isset($config['decorator']) || true !== $config['decorator'];
        });
    }

    private static function processConfigsHeirs(array $configs)
    {
        foreach ($configs as $parentName => &$config) {
            if (!empty($config[self::HEIRS_KEY])) {
                // allows shorthand configuration `heirs: QueryFooDecorator` is equivalent to `heirs: [QueryFooDecorator]`
                if (!\is_array($config[self::HEIRS_KEY])) {
                    $config[self::HEIRS_KEY] = [$config[self::HEIRS_KEY]];
                }
                foreach ($config[self::HEIRS_KEY] as $heirs) {
                    if (!isset($configs[$heirs])) {
                        throw new \InvalidArgumentException(\sprintf(
                            'Type %s child of %s not found.',
                            \json_encode($heirs),
                            \json_encode($parentName)
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

    private static function processConfigsInherits(array $configs)
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

    private static function inheritsTypeConfig($child, array $parents, array $configs)
    {
        $parentTypes = \array_intersect_key($configs, \array_flip($parents));
        $parentTypes = \array_reverse($parentTypes);
        $mergedParentsConfig = \call_user_func_array('array_replace_recursive', \array_column($parentTypes, 'config'));
        $childType = $configs[$child];
        // unset resolveType field resulting from the merge of a "interface" type
        if ('object' === $childType['type']) {
            unset($mergedParentsConfig['resolveType']);
        }

        $configs = \array_replace_recursive(['config' => $mergedParentsConfig], $childType);

        return $configs;
    }

    private static function flattenInherits($name, array $configs, array $allowedTypes, $child = null, array $typesTreated = [])
    {
        self::checkTypeExists($name, $configs, $child);
        self::checkCircularReferenceInheritsTypes($name, $typesTreated);
        self::checkAllowedInheritsTypes($name, $configs[$name], $allowedTypes, $child);

        // flatten
        $config = $configs[$name];
        if (empty($config[self::INHERITS_KEY]) || !\is_array($config[self::INHERITS_KEY])) {
            return [];
        }
        $typesTreated[$name] = true;
        $flattenInheritsTypes = [];
        foreach ($config[self::INHERITS_KEY] as $typeToInherit) {
            $flattenInheritsTypes[] = $typeToInherit;
            $flattenInheritsTypes = \array_merge(
                $flattenInheritsTypes,
                self::flattenInherits($typeToInherit, $configs, $allowedTypes, $name, $typesTreated)
            );
        }

        return $flattenInheritsTypes;
    }

    private static function checkTypeExists($name, array $configs, $child)
    {
        if (!isset($configs[$name])) {
            throw new \InvalidArgumentException(\sprintf(
                'Type %s inherits by %s not found.',
                \json_encode($name),
                \json_encode($child)
            ));
        }
    }

    private static function checkCircularReferenceInheritsTypes($name, array $typesTreated)
    {
        if (isset($typesTreated[$name])) {
            throw new \InvalidArgumentException(\sprintf(
                'Type circular inheritance detected (%s).',
                \implode('->', \array_merge(\array_keys($typesTreated), [$name]))
            ));
        }
    }

    private static function checkAllowedInheritsTypes($name, array $config, array $allowedTypes, $child)
    {
        if (empty($config['decorator']) && isset($config['type']) && !\in_array($config['type'], $allowedTypes)) {
            throw new \InvalidArgumentException(\sprintf(
                'Type %s can\'t inherits %s because %s is not allowed type (%s).',
                \json_encode($name),
                \json_encode($child),
                \json_encode($config['type']),
                \json_encode($allowedTypes)
            ));
        }
    }
}
