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
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser as YamlParser;

class OverblogGraphQLTypesExtension extends Extension
{
    private $yamlParser;

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $builderId = $this->getAliasPrefix().'.type_builder';

        foreach ($config as $name => $options) {
            $customTypeId = sprintf('%s.definition.custom_%s_type', $this->getAliasPrefix(), $container->underscore($name));

            $options['config']['name'] = $name;

            $container
                ->setDefinition($customTypeId, new Definition('GraphQL\\Type\\Definition\\Type'))
                ->setFactory([new Reference($builderId), 'create'])
                ->setArguments([$options['type'], $options['config']])
                ->addTag($this->getAliasPrefix().'.type', ['alias' => $name])
            ;
        }
    }

    public function containerPrependExtensionConfig(array $config, ContainerBuilder $container)
    {
        $typesMappings = array_merge(
            $this->typesConfigsMappingFromConfig($config, $container),
            $this->typesConfigsMappingFromBundles($container)
        );

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
            $typeConfig = 'yml' === $type ? $this->typesConfigFromYml($file, $container) : $this->typesConfigFromXml($file, $container);
            $container->prependExtensionConfig($this->getAlias(), $typeConfig);
        }
    }

    private function typesConfigFromXml(SplFileInfo $file, ContainerBuilder $container)
    {
        $typesConfig = [];

        try {
            //@todo fix xml validateSchema
            $xml = XmlUtils::loadFile($file->getRealPath());
            foreach ($xml->documentElement->childNodes as $node) {
                if (!$node instanceof \DOMElement) {
                    continue;
                }
                $values = XmlUtils::convertDomElementToArray($node);
                if (!is_array($values)) {
                    continue;
                }
                $typesConfig = array_merge($typesConfig, $values);
            }
            $container->addResource(new FileResource($file->getRealPath()));
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException(sprintf('Unable to parse file "%s".', $file), $e->getCode(), $e);
        }

        return $typesConfig;
    }

    private function typesConfigFromYml(SplFileInfo $file, ContainerBuilder $container)
    {
        if (null === $this->yamlParser) {
            $this->yamlParser = new YamlParser();
        }

        try {
            $typesConfig = $this->yamlParser->parse($file->getContents());
            $container->addResource(new FileResource($file->getRealPath()));
        } catch (ParseException $e) {
            throw new InvalidArgumentException(sprintf('The file "%s" does not contain valid YAML.', $file), 0, $e);
        }

        return $typesConfig;
    }

    private function typesConfigsMappingFromConfig(array $config, ContainerBuilder $container)
    {
        $typesMappings = [];
        // from config
        if (!empty($config['definitions']['mappings']['types'])) {
            $typesMappings = array_filter(array_map(
                function (array $typeMapping) use ($container) {

                    $params = $this->detectConfigFiles($container, $typeMapping['dir'],  $typeMapping['type']);

                    return $params;
                },
                $config['definitions']['mappings']['types']
            ));
        }

        return $typesMappings;
    }

    private function typesConfigsMappingFromBundles(ContainerBuilder $container)
    {
        $typesMappings = [];
        $bundles = $container->getParameter('kernel.bundles');

        // auto detect from bundle
        foreach ($bundles as $name => $class) {
            $bundle = new \ReflectionClass($class);
            $bundleDir = dirname($bundle->getFileName());

            $configPath = $bundleDir.'/'.$this->getMappingResourceConfigDirectory();
            $params = $this->detectConfigFiles($container, $configPath);

            if (null !== $params) {
                $typesMappings[] = $params;
            }
        }

        return $typesMappings;
    }

    private function detectConfigFiles(ContainerBuilder $container, $configPath, $type = null)
    {
        // add the closest existing directory as a resource
        $resource = $configPath;
        while (!is_dir($resource)) {
            $resource = dirname($resource);
        }
        $container->addResource(new FileResource($resource));

        $extension = $this->getMappingResourceExtension();
        $finder = new Finder();

        $types = null === $type ? ['yml', 'xml'] : [$type];

        foreach ($types as $type) {
            try {
                $finder->files()->in($configPath)->name('*.'.$extension.'.'.$type);
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

    private function getMappingResourceConfigDirectory()
    {
        return 'Resources/config/graphql';
    }

    private function getMappingResourceExtension()
    {
        return 'types';
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
