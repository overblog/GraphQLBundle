<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\DependencyInjection;

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OverblogGraphQLTypesExtension extends Extension
{
    private static $configTypes = ['yaml', 'xml'];

    private static $typeExtensions = ['yaml' => '{yaml,yml}', 'xml' => 'xml'];

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter($this->getAlias().'.config', $config);
    }

    public function containerPrependExtensionConfig(array $config, ContainerBuilder $container)
    {
        $typesMappings = $this->mappingConfig($config, $container);

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
        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $parserClass = sprintf('Overblog\\GraphQLBundle\\Config\\Parser\\%sParser', ucfirst($type));

            $typeConfig = call_user_func($parserClass.'::parse', $file, $container);
            $container->prependExtensionConfig($this->getAlias(), $typeConfig);
        }
    }

    private function mappingConfig(array $config, ContainerBuilder $container)
    {
        $typesMappings = empty($config['definitions']['mappings']['types']) ? [] : $config['definitions']['mappings']['types'];

        // app only config files (yml or xml)
        if ($container->hasParameter('kernel.root_dir')) {
            $typesMappings[] = ['dir' => $container->getParameter('kernel.root_dir').'/config/graphql', 'type' => null];
        }

        $mappingFromBundles = $this->mappingFromBundles($container);
        $typesMappings = array_merge($typesMappings, $mappingFromBundles);

        // from config
        $typesMappings = array_filter(array_map(
            function (array $typeMapping) use ($container) {
                $params = $this->detectFilesByType($container, $typeMapping['dir'],  $typeMapping['type']);

                return $params;
            },
            $typesMappings
        ));

        return $typesMappings;
    }

    private function mappingFromBundles(ContainerBuilder $container)
    {
        $typesMappings = [];
        $bundles = $container->getParameter('kernel.bundles');

        // auto detect from bundle
        foreach ($bundles as $name => $class) {
            $bundle = new \ReflectionClass($class);
            $bundleDir = dirname($bundle->getFileName());

            // only config files (yml or xml)
            $typesMappings[] = ['dir' => $bundleDir.'/Resources/config/graphql', 'type' => null];
        }

        return $typesMappings;
    }

    private function detectFilesByType(ContainerBuilder $container, $path, $type = null)
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
                $finder->files()->in($path)->name('*.types.'.self::$typeExtensions[$type]);
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
