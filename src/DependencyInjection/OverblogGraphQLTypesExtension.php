<?php

namespace Overblog\GraphQLBundle\DependencyInjection;

use Overblog\GraphQLBundle\Config\Parser\GraphQLParser;
use Overblog\GraphQLBundle\Config\Parser\XmlParser;
use Overblog\GraphQLBundle\Config\Parser\YamlParser;
use Overblog\GraphQLBundle\OverblogGraphQLBundle;
use Symfony\Component\Config\Definition\Exception\ForbiddenOverwriteException;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OverblogGraphQLTypesExtension extends Extension
{
    const SUPPORTED_TYPES_EXTENSIONS = ['yaml' => '{yaml,yml}', 'xml' => 'xml', 'graphql' => '{graphql,graphqls}'];

    const PARSERS = [
        'yaml' => YamlParser::class,
        'xml' => XmlParser::class,
        'graphql' => GraphQLParser::class,
    ];

    private static $defaultDefaultConfig = [
        'definitions' => [
            'mappings' => [
                'auto_discover' => [
                    'root_dir' => true,
                    'bundles' => true,
                ],
                'types' => [],
            ],
        ],
    ];

    private $treatedFiles = [];

    const DEFAULT_TYPES_SUFFIX = '.types';

    public function load(array $configs, ContainerBuilder $container)
    {
        $configs = \array_filter($configs);
        //$configs = array_filter($configs);
        if (\count($configs) > 1) {
            throw new \InvalidArgumentException('Configs type should never contain more than one config to deal with inheritance.');
        }
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter($this->getAlias().'.config', $config);
    }

    public function containerPrependExtensionConfig(array $configs, ContainerBuilder $container)
    {
        $typesMappings = $this->mappingConfig($configs, $container);
        // reset treated files
        $this->treatedFiles = [];
        $typesMappings = \call_user_func_array('array_merge', $typesMappings);
        $typeConfigs = [];
        // treats mappings
        foreach ($typesMappings as $params) {
            $typeConfigs = \array_merge($typeConfigs, $this->parseTypeConfigFiles($params['type'], $params['files'], $container));
        }

        $this->checkTypesDuplication($typeConfigs);
        // flatten config is a requirement to support inheritance
        $flattenTypeConfig = \call_user_func_array('array_merge', $typeConfigs);

        $container->prependExtensionConfig($this->getAlias(), $flattenTypeConfig);
    }

    /**
     * @param $type
     * @param SplFileInfo[]    $files
     * @param ContainerBuilder $container
     *
     * @return array
     */
    private function parseTypeConfigFiles($type, $files, ContainerBuilder $container)
    {
        $config = [];
        foreach ($files as $file) {
            $fileRealPath = $file->getRealPath();
            if (isset($this->treatedFiles[$fileRealPath])) {
                continue;
            }

            $config[] = \call_user_func(self::PARSERS[$type].'::parse', $file, $container);
            $this->treatedFiles[$file->getRealPath()] = true;
        }

        return $config;
    }

    private function checkTypesDuplication(array $typeConfigs)
    {
        $types = \call_user_func_array('array_merge', \array_map('array_keys', $typeConfigs));
        $duplications = \array_keys(\array_filter(\array_count_values($types), function ($count) {
            return $count > 1;
        }));
        if (!empty($duplications)) {
            throw new ForbiddenOverwriteException(\sprintf(
                'Types (%s) cannot be overwritten. See inheritance doc section for more details.',
                \implode(', ', \array_map('json_encode', $duplications))
            ));
        }
    }

    private function mappingConfig(array $config, ContainerBuilder $container)
    {
        // use default value if needed
        $config = \array_replace_recursive(self::$defaultDefaultConfig, $config);

        $mappingConfig = $config['definitions']['mappings'];
        $typesMappings = $mappingConfig['types'];

        // app only config files (yml or xml or graphql)
        if ($mappingConfig['auto_discover']['root_dir'] && $container->hasParameter('kernel.root_dir')) {
            $typesMappings[] = ['dir' => $container->getParameter('kernel.root_dir').'/config/graphql', 'types' => null];
        }
        if ($mappingConfig['auto_discover']['bundles']) {
            $mappingFromBundles = $this->mappingFromBundles($container);
            $typesMappings = \array_merge($typesMappings, $mappingFromBundles);
        } else {
            // enabled only for this bundle
            $typesMappings[] = [
                'dir' => $this->bundleDir(OverblogGraphQLBundle::class).'/Resources/config/graphql',
                'types' => ['yaml'],
            ];
        }

        // from config
        $typesMappings = $this->detectFilesFromTypesMappings($typesMappings, $container);

        return $typesMappings;
    }

    private function detectFilesFromTypesMappings(array $typesMappings, ContainerBuilder $container)
    {
        return \array_filter(\array_map(
            function (array $typeMapping) use ($container) {
                $suffix = isset($typeMapping['suffix']) ? $typeMapping['suffix'] : '';
                $types = isset($typeMapping['types']) ? $typeMapping['types'] : null;
                $params = $this->detectFilesByTypes($container, $typeMapping['dir'], $suffix, $types);

                return $params;
            },
            $typesMappings
        ));
    }

    private function mappingFromBundles(ContainerBuilder $container)
    {
        $typesMappings = [];
        $bundles = $container->getParameter('kernel.bundles');

        // auto detect from bundle
        foreach ($bundles as $name => $class) {
            $bundleDir = $this->bundleDir($class);

            // only config files (yml or xml)
            $typesMappings[] = ['dir' => $bundleDir.'/Resources/config/graphql', 'types' => null];
        }

        return $typesMappings;
    }

    private function detectFilesByTypes(ContainerBuilder $container, $path, $suffix, array $types = null)
    {
        // add the closest existing directory as a resource
        $resource = $path;
        while (!\is_dir($resource)) {
            $resource = \dirname($resource);
        }
        $container->addResource(new FileResource($resource));

        $stopOnFirstTypeMatching = empty($types);

        $types = $stopOnFirstTypeMatching ? \array_keys(self::SUPPORTED_TYPES_EXTENSIONS) : $types;
        $files = [];

        foreach ($types as $type) {
            $finder = Finder::create();
            try {
                $finder->files()->in($path)->name(\sprintf('*%s.%s', $suffix, self::SUPPORTED_TYPES_EXTENSIONS[$type]));
            } catch (\InvalidArgumentException $e) {
                continue;
            }
            if ($finder->count() > 0) {
                $files[] = [
                    'type' => $type,
                    'files' => $finder,
                ];
                if ($stopOnFirstTypeMatching) {
                    break;
                }
            }
        }

        return $files;
    }

    private function bundleDir($bundleClass)
    {
        $bundle = new \ReflectionClass($bundleClass);
        $bundleDir = \dirname($bundle->getFileName());

        return $bundleDir;
    }

    public function getAliasPrefix()
    {
        return 'overblog_graphql';
    }

    public function getAlias()
    {
        return $this->getAliasPrefix().'_types';
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new TypesConfiguration();
    }
}
