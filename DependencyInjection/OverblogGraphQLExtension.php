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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser as YamlParser;

class OverblogGraphQLExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('graphql_types.yml');
        $loader->load('graphql_fields.yml');
        $loader->load('graphql_args.yml');

        $configs = $this->addTypesDefinitionFromBundles($configs, $container->getParameter('kernel.bundles'));

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        if (isset($config['services'])) {
            foreach ($config['services'] as $name => $id) {
                $alias = sprintf('%s.%s', $this->getAlias(), $name);
                $container->setAlias($alias, $id);
            }
        }

        if (isset($config['definitions']['types'])) {
            $builderId = $this->getAlias().'.type_builder';

            foreach ($config['definitions']['types'] as $name => $options) {
                $customTypeId = sprintf('%s.definition.custom_%s_type', $this->getAlias(), $container->underscore($name));

                $options['config']['name'] = $name;

                $container
                    ->setDefinition($customTypeId, new Definition('GraphQL\\Type\\Definition\\Type'))
                    ->setFactory([new Reference($builderId), 'create'])
                    ->setArguments([$options['type'], $options['config']])
                    ->addTag($this->getAlias().'.type', ['alias' => $name])
                ;
            }
        }

        $container->getDefinition($this->getAlias().'.schema_builder')
            ->replaceArgument(2, $config['definitions']['config_validation']);

        if (isset($config['definitions']['schema'])) {
            $container
                ->getDefinition($this->getAlias().'.schema')
                ->replaceArgument(0, $config['definitions']['schema']['query'])
                ->replaceArgument(1, $config['definitions']['schema']['mutation'])
                ->replaceArgument(2, $config['definitions']['schema']['subscription'])
                ->setPublic(true)
            ;
        }

        if (isset($config['definitions']['internal_error_message'])) {
            $container
                ->getDefinition($this->getAlias().'.error_handler')
                ->replaceArgument(0, $config['definitions']['internal_error_message'])
                ->setPublic(true)
            ;
        }

        if (isset($config['templates']['graphiql'])) {
            $container->setParameter('overblog_graphql.graphiql_template', $config['templates']['graphiql']);
        }
    }

    private function addTypesDefinitionFromBundles(array $configs, array $bundles)
    {
        $types = [];
        $yamlParser = null;
        foreach ($bundles as $name => $class) {
            $bundle = new \ReflectionClass($class);
            $bundleDir = dirname($bundle->getFileName());

            $params = $this->detectConfigFiles($bundleDir);

            if (null === $params) {
                continue;
            }
            $extension = $this->getMappingResourceExtension();
            /** @var SplFileInfo $file */
            foreach ($params['files'] as $file) {
                switch ($params['type']) {
                    case 'yml':
                        if (null === $yamlParser) {
                            $yamlParser = new YamlParser();
                        }
                        try {
                            $types = array_merge($types, $yamlParser->parse($file->getContents()));
                        } catch (ParseException $e) {
                            throw new InvalidArgumentException(sprintf('The file "%s" does not contain valid YAML.', $file), 0, $e);
                        }
                        break;

                    case 'xml':
                        try {
                            //@todo fix xml validateSchema
                            $xml = XmlUtils::loadFile($file->getRealPath());//, array($this, 'validateSchema'));
                            foreach ($xml->documentElement->childNodes as $node) {
                                if (!$node instanceof \DOMElement) {
                                    continue;
                                }
                                $values = XmlUtils::convertDomElementToArray($node);
                                if (!is_array($values)) {
                                    continue;
                                }
                                $types = array_merge($types, $values);
                            }
                        } catch (\InvalidArgumentException $e) {
                            throw new InvalidArgumentException(sprintf('Unable to parse file "%s".', $file), $e->getCode(), $e);
                        }
                        break;
                }
            }
        }

        if (empty($configs[0]['definitions']['types'])) {
            $configs[0]['definitions']['types'] = $types;
        } else {
            $configs[0]['definitions']['types'] = array_merge($configs[0]['definitions']['types'], $types);
        }

        return $configs;
    }

    private function detectConfigFiles($dir)
    {
        $configPath = $dir.'/'.$this->getMappingResourceConfigDirectory();
        $extension = $this->getMappingResourceExtension();
        $finder = new Finder();

        foreach (['xml', 'yml'] as $type) {
            try {
                $finder->files()->in($configPath)->name('*'.$extension.'.'.$type);
            } catch (\InvalidArgumentException $e) {
                continue;
            }
            if (0 === $finder->count()) {
                continue;
            }

            return [
                'type'  => $type,
                'files' => $finder,
            ];
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

    public function getAlias()
    {
        return 'overblog_graphql';
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }
}
