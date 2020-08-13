<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use InvalidArgumentException;
use Overblog\GraphQLBundle\Config\Parser\AnnotationParser;
use Overblog\GraphQLBundle\Config\Parser\GraphQLParser;
use Overblog\GraphQLBundle\Config\Parser\PreParserInterface;
use Overblog\GraphQLBundle\Config\Parser\XmlParser;
use Overblog\GraphQLBundle\Config\Parser\YamlParser;
use Overblog\GraphQLBundle\DependencyInjection\TypesConfiguration;
use Overblog\GraphQLBundle\OverblogGraphQLBundle;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Config\Definition\Exception\ForbiddenOverwriteException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use function array_count_values;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_replace_recursive;
use function call_user_func;
use function dirname;
use function implode;
use function is_a;
use function is_dir;
use function sprintf;

class ConfigParserPass implements CompilerPassInterface
{
    public const SUPPORTED_TYPES_EXTENSIONS = [
        'yaml' => '{yaml,yml}',
        'xml' => 'xml',
        'graphql' => '{graphql,graphqls}',
        'annotation' => 'php',
    ];

    public const PARSERS = [
        'yaml' => YamlParser::class,
        'xml' => XmlParser::class,
        'graphql' => GraphQLParser::class,
        'annotation' => AnnotationParser::class,
    ];

    private static array $defaultDefaultConfig = [
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

    private array $treatedFiles = [];
    private array $preTreatedFiles = [];

    public const DEFAULT_TYPES_SUFFIX = '.types';

    public function process(ContainerBuilder $container): void
    {
        $config = $this->processConfiguration([$this->getConfigs($container)]);
        $container->setParameter($this->getAlias().'.config', $config);
    }

    public function processConfiguration(array $configs): array
    {
        return (new Processor())->processConfiguration(new TypesConfiguration(), $configs);
    }

    private function getConfigs(ContainerBuilder $container): array
    {
        $config = $container->getParameterBag()->resolveValue($container->getParameter('overblog_graphql.config'));
        $container->getParameterBag()->remove('overblog_graphql.config');
        $container->setParameter($this->getAlias().'.classes_map', []);
        $typesMappings = $this->mappingConfig($config, $container);
        // reset treated files
        $this->treatedFiles = [];
        $typesMappings = array_merge(...$typesMappings);
        $typeConfigs = [];

        // treats mappings
        // Pre-parse all files
        AnnotationParser::reset();
        $typesNeedPreParsing = $this->typesNeedPreParsing();
        foreach ($typesMappings as $params) {
            if ($typesNeedPreParsing[$params['type']]) {
                $this->parseTypeConfigFiles($params['type'], $params['files'], $container, $config, true);
            }
        }

        // Parse all files and get related config
        foreach ($typesMappings as $params) {
            $typeConfigs = array_merge($typeConfigs, $this->parseTypeConfigFiles($params['type'], $params['files'], $container, $config));
        }

        $this->checkTypesDuplication($typeConfigs);
        // flatten config is a requirement to support inheritance
        $flattenTypeConfig = array_merge(...$typeConfigs);

        return $flattenTypeConfig;
    }

    private function typesNeedPreParsing(): array
    {
        $needPreParsing = [];
        foreach (self::PARSERS as $type => $className) {
            $needPreParsing[$type] = is_a($className, PreParserInterface::class, true);
        }

        return $needPreParsing;
    }

    /**
     * @param SplFileInfo[] $files
     */
    private function parseTypeConfigFiles(string $type, iterable $files, ContainerBuilder $container, array $configs, bool $preParse = false): array
    {
        if ($preParse) {
            $method = 'preParse';
            $treatedFiles = &$this->preTreatedFiles;
        } else {
            $method = 'parse';
            $treatedFiles = &$this->treatedFiles;
        }

        $config = [];
        foreach ($files as $file) {
            $fileRealPath = $file->getRealPath();
            if (isset($treatedFiles[$fileRealPath])) {
                continue;
            }

            $config[] = call_user_func([self::PARSERS[$type], $method], $file, $container, $configs);
            $treatedFiles[$file->getRealPath()] = true;
        }

        return $config;
    }

    private function checkTypesDuplication(array $typeConfigs): void
    {
        $types = array_merge(...array_map('array_keys', $typeConfigs));
        $duplications = array_keys(array_filter(array_count_values($types), function ($count) {
            return $count > 1;
        }));
        if (!empty($duplications)) {
            throw new ForbiddenOverwriteException(sprintf(
                'Types (%s) cannot be overwritten. See inheritance doc section for more details.',
                implode(', ', array_map('json_encode', $duplications))
            ));
        }
    }

    private function mappingConfig(array $config, ContainerBuilder $container): array
    {
        // use default value if needed
        $config = array_replace_recursive(self::$defaultDefaultConfig, $config);

        $mappingConfig = $config['definitions']['mappings'];
        $typesMappings = $mappingConfig['types'];

        // app only config files (yml or xml or graphql)
        if ($mappingConfig['auto_discover']['root_dir'] && $container->hasParameter('kernel.root_dir')) {
            $typesMappings[] = ['dir' => $container->getParameter('kernel.root_dir').'/config/graphql', 'types' => null];
        }
        if ($mappingConfig['auto_discover']['bundles']) {
            $mappingFromBundles = $this->mappingFromBundles($container);
            $typesMappings = array_merge($typesMappings, $mappingFromBundles);
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

    private function detectFilesFromTypesMappings(array $typesMappings, ContainerBuilder $container): array
    {
        return array_filter(array_map(
            function (array $typeMapping) use ($container) {
                $suffix = $typeMapping['suffix'] ?? '';
                $types = $typeMapping['types'] ?? null;

                return $this->detectFilesByTypes($container, $typeMapping['dir'], $suffix, $types);
            },
            $typesMappings
        ));
    }

    private function mappingFromBundles(ContainerBuilder $container): array
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

    private function detectFilesByTypes(ContainerBuilder $container, string $path, string $suffix, array $types = null): array
    {
        // add the closest existing directory as a resource
        $resource = $path;
        while (!is_dir($resource)) {
            $resource = dirname($resource);
        }
        $container->addResource(new FileResource($resource));

        $stopOnFirstTypeMatching = empty($types);

        $types = $stopOnFirstTypeMatching ? array_keys(self::SUPPORTED_TYPES_EXTENSIONS) : $types;
        $files = [];

        foreach ($types as $type) {
            $finder = Finder::create();
            try {
                $finder->files()->in($path)->name(sprintf('*%s.%s', $suffix, self::SUPPORTED_TYPES_EXTENSIONS[$type]));
            } catch (InvalidArgumentException $e) {
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

    /**
     * @throws ReflectionException
     */
    private function bundleDir(string $bundleClass): string
    {
        $bundle = new ReflectionClass($bundleClass); // @phpstan-ignore-line

        return dirname($bundle->getFileName());
    }

    private function getAliasPrefix(): string
    {
        return 'overblog_graphql';
    }

    private function getAlias(): string
    {
        return $this->getAliasPrefix().'_types';
    }
}
