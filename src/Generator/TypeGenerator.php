<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator;

use Composer\Autoload\ClassLoader;
use Murtukov\PHPCodeGenerator\GeneratorInterface;
use Overblog\GraphQLBundle\Config\Processor;
use Overblog\GraphQLBundle\Generator\TypeBuilder\CustomScalarTypeBuilder;
use Overblog\GraphQLBundle\Generator\TypeBuilder\EnumTypeBuilder;
use Overblog\GraphQLBundle\Generator\TypeBuilder\InputTypeBuilder;
use Overblog\GraphQLBundle\Generator\TypeBuilder\InterfaceTypeBuilder;
use Overblog\GraphQLBundle\Generator\TypeBuilder\ObjectTypeBuilder;
use Overblog\GraphQLBundle\Generator\TypeBuilder\UnionTypeBuilder;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Filesystem\Filesystem;

class TypeGenerator
{
    public const MODE_DRY_RUN = 1;
    public const MODE_MAPPING_ONLY = 2;
    public const MODE_WRITE = 4;
    public const MODE_OVERRIDE = 8;

    public const USE_FOR_CLOSURES = '$globalVariables';
    public const DEFAULT_CONFIG_PROCESSOR = [Processor::class, 'process'];

    private static bool $classMapLoaded = false;
    private ?string $cacheDir;
    protected int $cacheDirMask;
    private $configProcessor;
    private array $configs;
    private bool $useClassMap;
    private ?string $baseCacheDir;
    private string $classNamespace;

    private ServiceLocator $typeBuilders;

    public function __construct(
        string $classNamespace,
        ?string $cacheDir,
        array $configs,
        ServiceLocator $typeBuilders,
        bool $useClassMap = true,
        ?string $baseCacheDir = null,
        ?int $cacheDirMask = null
    ) {
        $this->setCacheDir($cacheDir);
        $this->configProcessor = static::DEFAULT_CONFIG_PROCESSOR;
        $this->configs = $configs;
        $this->useClassMap = $useClassMap;
        $this->baseCacheDir = $baseCacheDir;
        $this->typeBuilders = $typeBuilders;
        $this->classNamespace = $classNamespace;

        if (null === $cacheDirMask) {
            // we apply permission 0777 for default cache dir otherwise we apply 0775.
            $cacheDirMask = null === $cacheDir ? 0777 : 0775;
        }

        $this->cacheDirMask = $cacheDirMask;
    }

    /**
     * @return string|null
     */
    public function getBaseCacheDir(): ?string
    {
        return $this->baseCacheDir;
    }

    /**
     * @param string|null $baseCacheDir
     */
    public function setBaseCacheDir($baseCacheDir): void
    {
        $this->baseCacheDir = $baseCacheDir;
    }

    public function getCacheDir(bool $useDefault = true): ?string
    {
        if ($useDefault) {
            return $this->cacheDir ?: $this->baseCacheDir.'/overblog/graphql-bundle/__definitions__';
        } else {
            return $this->cacheDir;
        }
    }

    public function setCacheDir(?string $cacheDir): self
    {
        $this->cacheDir = $cacheDir;

        return $this;
    }

    public function compile(int $mode): array
    {
        $cacheDir = $this->getCacheDir();
        $writeMode = $mode & self::MODE_WRITE;

        // Configure write mode
        if ($writeMode && \file_exists($cacheDir)) {
            $fs = new Filesystem();
            $fs->remove($cacheDir);
        }

        // Process configs
        $configs = ($this->configProcessor)($this->configs);

        // Generate classes
        $classes = [];
        foreach ($configs as $name => $config) {
            $config['config']['name'] ??= $name;
            $classMap = $this->generateClass($config, $cacheDir, $mode);
            $classes = \array_merge($classes, $classMap);
        }

        // Create class map file
        if ($writeMode && $this->useClassMap) {
            $content = "<?php\nreturn ".\var_export($classes, true).';';
            // replaced hard-coding absolute path by __DIR__ (see https://github.com/overblog/GraphQLBundle/issues/167)
            $content = \str_replace(' => \''.$cacheDir, ' => __DIR__ . \'', $content);

            \file_put_contents($this->getClassesMap(), $content);

            $this->loadClasses(true);
        }

        return $classes;
    }

    public function generateClass(array $config, ?string $outputDirectory, int $mode = self::MODE_WRITE): array
    {
        $className = $config['config']['name'].'Type';
        $path = "$outputDirectory/$className.php";

        $phpFile = $this->buildClass($config['config'], $config['type']);
        $phpFile->save($path);

        return ["$this->classNamespace\\$className" => $path];
    }

    public function loadClasses(bool $forceReload = false): void
    {
        if ($this->useClassMap && (!self::$classMapLoaded || $forceReload)) {
            $classMapFile = $this->getClassesMap();
            $classes = \file_exists($classMapFile) ? require $classMapFile : [];

            /** @var ClassLoader $mapClassLoader */
            static $mapClassLoader = null;

            if (null === $mapClassLoader) {
                $mapClassLoader = new ClassLoader();
                $mapClassLoader->setClassMapAuthoritative(true);
            } else {
                $mapClassLoader->unregister();
            }

            $mapClassLoader->addClassMap($classes);
            $mapClassLoader->register();

            self::$classMapLoaded = true;
        }
    }

    private function getClassesMap(): string
    {
        return $this->getCacheDir().'/__classes.map';
    }


    public function buildClass(array $config, string $type): GeneratorInterface
    {
        switch ($type) {
            case 'object':
                return $this->typeBuilders->get(ObjectTypeBuilder::class)->build($config);
            case 'input':
            case 'input-object':
                return $this->typeBuilders->get(InputTypeBuilder::class)->build($config);
            case 'custom-scalar':
                return $this->typeBuilders->get(CustomScalarTypeBuilder::class)->build($config);
            case 'interface':
                return $this->typeBuilders->get(InterfaceTypeBuilder::class)->build($config);
            case 'union':
                return $this->typeBuilders->get(UnionTypeBuilder::class)->build($config);
            case 'enum':
                return $this->typeBuilders->get(EnumTypeBuilder::class)->build($config);
            default:
                throw new \RuntimeException("GraphQL config type is not recognized: '$type'");
        }
    }
}
