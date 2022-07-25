<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use InvalidArgumentException;
use Overblog\GraphQLBundle\Config\Parser\AnnotationParser;
use Overblog\GraphQLBundle\Config\Parser\AttributeParser;
use Overblog\GraphQLBundle\Config\Parser\GraphQLParser;
use Overblog\GraphQLBundle\Config\Parser\ParserInterface;
use Overblog\GraphQLBundle\Config\Parser\PreParserInterface;
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
use function dirname;
use function implode;
use function is_a;
use function is_dir;
use function sprintf;

class ConfigParserPass implements CompilerPassInterface
{
    public const TYPE_YAML = 'yaml';
    public const TYPE_GRAPHQL = 'graphql';
    public const TYPE_ANNOTATION = 'annotation';
    public const TYPE_ATTRIBUTE = 'attribute';

    public const SUPPORTED_TYPES = [
        self::TYPE_YAML,
        self::TYPE_GRAPHQL,
        self::TYPE_ANNOTATION,
        self::TYPE_ATTRIBUTE,
    ];

    public const SUPPORTED_TYPES_EXTENSIONS = [
        self::TYPE_YAML => '{yaml,yml}',
        self::TYPE_GRAPHQL => '{graphql,graphqls}',
        self::TYPE_ANNOTATION => 'php',
        self::TYPE_ATTRIBUTE => 'php',
    ];

    /**
     * @deprecated They are going to be configurable.
     * @var array<string, class-string<ParserInterface|PreParserInterface>>
     */
    public const PARSERS = [
        self::TYPE_YAML => YamlParser::class,
        self::TYPE_GRAPHQL => GraphQLParser::class,
        self::TYPE_ANNOTATION => AnnotationParser::class,
        self::TYPE_ATTRIBUTE => AttributeParser::class,
    ];

    private const DEFAULT_CONFIG = [
        'definitions' => [
            'mappings' => [
                'auto_discover' => [
                    'root_dir' => true,
                    'bundles' => true,
                    'built_in' => true,
                ],
                'types' => [],
            ],
        ],
        'parsers' => self::PARSERS,
    ];

    /**
     * @deprecated Use {@see ConfigParserPass::PARSERS }. Added for the backward compatibility.
     * @var array<string,array<string,mixed>>
     */
    private static array $defaultDefaultConfig = self::DEFAULT_CONFIG;

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

        // use default value if needed
        $config = array_replace_recursive(self::DEFAULT_CONFIG, $config);

        $typesMappings = $this->mappingConfig($config, $container);
        // reset treated files
        $this->treatedFiles = [];
        $typesMappings = array_merge(...$typesMappings);
        $typeConfigs = [];

        // treats mappings
        // Pre-parse all files
        AnnotationParser::reset($config);
        AttributeParser::reset($config);
        $typesNeedPreParsing = $this->typesNeedPreParsing($config['parsers']);
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

    /**
     * @param array<string,string> $parsers
     *
     * @return array<string,bool>
     */
    private function typesNeedPreParsing(array $parsers): array
    {
        $needPreParsing = [];
        foreach ($parsers as $type => $className) {
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

            $parser = [$configs['parsers'][$type], $method];
            if (is_callable($parser)) {
                $config[] = ($parser)($file, $container, $configs);
            }
            $treatedFiles[$file->getRealPath()] = true;
        }

        return $config;
    }

    private function checkTypesDuplication(array $typeConfigs): void
    {
        $types = array_merge(...array_map('array_keys', $typeConfigs));
        $duplications = array_keys(array_filter(array_count_values($types), fn ($count) => $count > 1));
        if (!empty($duplications)) {
            throw new ForbiddenOverwriteException(sprintf(
                'Types (%s) cannot be overwritten. See inheritance doc section for more details.',
                implode(', ', array_map('json_encode', $duplications))
            ));
        }
    }

    private function mappingConfig(array $config, ContainerBuilder $container): array
    {
        $mappingConfig = $config['definitions']['mappings'];
        $typesMappings = $mappingConfig['types'];

        // app only config files (yml or xml or graphql)
        if ($mappingConfig['auto_discover']['root_dir'] && $container->hasParameter('kernel.root_dir')) {
            // @phpstan-ignore-next-line
            $typesMappings[] = ['dir' => $container->getParameter('kernel.root_dir').'/config/graphql', 'types' => null];
        }
        if ($mappingConfig['auto_discover']['bundles']) {
            $mappingFromBundles = $this->mappingFromBundles($container);
            $typesMappings = array_merge($typesMappings, $mappingFromBundles);
        }
        if ($mappingConfig['auto_discover']['built_in']) {
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

        /** @var array<string, class-string> $bundles */
        $bundles = $container->getParameter('kernel.bundles');

        // auto detect from bundle
        foreach ($bundles as $class) {
            // skip this bundle
            if (OverblogGraphQLBundle::class === $class) {
                continue;
            }

            $bundleDir = $this->bundleDir($class);

            // only config files (yml)
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
