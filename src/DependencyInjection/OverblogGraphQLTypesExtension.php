<?php

namespace Overblog\GraphQLBundle\DependencyInjection;

use Overblog\GraphQLBundle\OverblogGraphQLBundle;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OverblogGraphQLTypesExtension extends Extension
{
    private static $configTypes = ['yaml', 'xml'];

    private static $typeExtensions = ['yaml' => '{yaml,yml}', 'xml' => 'xml'];

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
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter($this->getAlias().'.config', $config);
    }

    public function containerPrependExtensionConfig(array $config, ContainerBuilder $container)
    {
        $typesMappings = $this->mappingConfig($config, $container);
        // reset treated files
        $this->treatedFiles = [];
        // treats mappings
        foreach ($typesMappings as $params) {
            $this->prependExtensionConfigFromFiles($params['type'], $params['files'], $container);
        }
    }

    /**
     * @param $type
     * @param SplFileInfo[]    $files
     * @param ContainerBuilder $container
     */
    private function prependExtensionConfigFromFiles($type, $files, ContainerBuilder $container)
    {
        foreach ($files as $file) {
            $fileRealPath = $file->getRealPath();
            if (isset($this->treatedFiles[$fileRealPath])) {
                continue;
            }

            $parserClass = sprintf('Overblog\\GraphQLBundle\\Config\\Parser\\%sParser', ucfirst($type));

            $typeConfig = call_user_func($parserClass.'::parse', $file, $container);
            $container->prependExtensionConfig($this->getAlias(), $typeConfig);
            $this->treatedFiles[$file->getRealPath()] = true;
        }
    }

    private function mappingConfig(array $config, ContainerBuilder $container)
    {
        // use default value if needed
        $config = array_replace_recursive(self::$defaultDefaultConfig, $config);

        $mappingConfig = $config['definitions']['mappings'];
        $typesMappings = $mappingConfig['types'];

        // app only config files (yml or xml)
        if ($mappingConfig['auto_discover']['root_dir'] && $container->hasParameter('kernel.root_dir')) {
            $typesMappings[] = ['dir' => $container->getParameter('kernel.root_dir').'/config/graphql', 'type' => null];
        }
        if ($mappingConfig['auto_discover']['bundles']) {
            $mappingFromBundles = $this->mappingFromBundles($container);
            $typesMappings = array_merge($typesMappings, $mappingFromBundles);
        } else {
            // enabled only for this bundle
            $typesMappings[] = [
                'dir' => $this->bundleDir(OverblogGraphQLBundle::class).'/Resources/config/graphql',
                'type' => 'yaml',
            ];
        }

        // from config
        $typesMappings = $this->detectFilesFromTypesMappings($typesMappings, $container);

        return $typesMappings;
    }

    private function detectFilesFromTypesMappings(array $typesMappings, ContainerBuilder $container)
    {
        return array_filter(array_map(
            function (array $typeMapping) use ($container) {
                $params = $this->detectFilesByType(
                    $container,
                    $typeMapping['dir'],
                    $typeMapping['type'],
                    isset($typeMapping['suffix']) ? $typeMapping['suffix'] : ''
                );

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
            $typesMappings[] = ['dir' => $bundleDir.'/Resources/config/graphql', 'type' => null];
        }

        return $typesMappings;
    }

    private function detectFilesByType(ContainerBuilder $container, $path, $type, $suffix)
    {
        // add the closest existing directory as a resource
        $resource = $path;
        while (!is_dir($resource)) {
            $resource = dirname($resource);
        }
        $container->addResource(new FileResource($resource));

        $finder = new Finder();

        $types = null === $type ? self::$configTypes : [$type];

        foreach ($types as $type) {
            try {
                $finder->files()->in($path)->name('*'.$suffix.'.'.self::$typeExtensions[$type]);
            } catch (\InvalidArgumentException $e) {
                continue;
            }
            if ($finder->count() > 0) {
                return [
                    'type' => $type,
                    'files' => $finder,
                ];
            }
        }

        return;
    }

    private function bundleDir($bundleClass)
    {
        $bundle = new \ReflectionClass($bundleClass);
        $bundleDir = dirname($bundle->getFileName());

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
