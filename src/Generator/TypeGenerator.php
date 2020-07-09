<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator;

use Composer\Autoload\ClassLoader;
use Overblog\GraphQLBundle\Config\Processor;
use Symfony\Component\Filesystem\Filesystem;
use function array_merge;
use function file_exists;
use function file_put_contents;
use function str_replace;
use function var_export;

/**
 * @final
 */
class TypeGenerator
{
    public const MODE_DRY_RUN = 1;
    public const MODE_MAPPING_ONLY = 2;
    public const MODE_WRITE = 4;
    public const MODE_OVERRIDE = 8;

    public const GLOBAL_VARS = 'globalVariables';

    private static bool $classMapLoaded = false;
    private ?string $cacheDir;
    protected int $cacheDirMask;
    private array $configs;
    private bool $useClassMap;
    private ?string $baseCacheDir;
    private string $classNamespace;
    private TypeBuilder $typeBuilder;

    public function __construct(
        string $classNamespace,
        ?string $cacheDir,
        array $configs,
        TypeBuilder $typeBuilder,
        bool $useClassMap = true,
        ?string $baseCacheDir = null,
        ?int $cacheDirMask = null
    ) {
        $this->cacheDir = $cacheDir;
        $this->configs = $configs;
        $this->useClassMap = $useClassMap;
        $this->baseCacheDir = $baseCacheDir;
        $this->typeBuilder = $typeBuilder;
        $this->classNamespace = $classNamespace;

        if (null === $cacheDirMask) {
            // Apply permission 0777 for default cache dir otherwise apply 0775.
            $cacheDirMask = null === $cacheDir ? 0777 : 0775;
        }

        $this->cacheDirMask = $cacheDirMask;
    }

    public function getBaseCacheDir(): ?string
    {
        return $this->baseCacheDir;
    }

    public function setBaseCacheDir(string $baseCacheDir): void
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
        if ($writeMode && file_exists($cacheDir)) {
            $fs = new Filesystem();
            $fs->remove($cacheDir);
        }

        // Process configs
        $configs = Processor::process($this->configs);

        // Generate classes
        $classes = [];
        foreach ($configs as $name => $config) {
            $config['config']['name'] ??= $name;
            $config['config']['class_name'] = $config['class_name'];
            $classMap = $this->generateClass($config, $cacheDir, $mode);
            $classes = array_merge($classes, $classMap);
        }

        // Create class map file
        if ($writeMode && $this->useClassMap) {
            $content = "<?php\nreturn ".var_export($classes, true).';';

            // replaced hard-coded absolute paths by __DIR__
            // (see https://github.com/overblog/GraphQLBundle/issues/167)
            $content = str_replace(" => '$cacheDir", " => __DIR__ . '", $content);

            file_put_contents($this->getClassesMap(), $content);

            $this->loadClasses(true);
        }

        return $classes;
    }

    public function generateClass(array $config, ?string $outputDirectory, int $mode = self::MODE_WRITE): array
    {
        $className = $config['config']['class_name'];
        $path = "$outputDirectory/$className.php";

        if (!($mode & self::MODE_MAPPING_ONLY)) {
            $phpFile = $this->typeBuilder->build($config['config'], $config['type']);

            if ($mode & self::MODE_WRITE) {
                if (($mode & self::MODE_OVERRIDE) || !file_exists($path)) {
                    $phpFile->save($path);
                }
            }
        }

        return ["$this->classNamespace\\$className" => $path];
    }

    public function loadClasses(bool $forceReload = false): void
    {
        if ($this->useClassMap && (!self::$classMapLoaded || $forceReload)) {
            $classMapFile = $this->getClassesMap();
            $classes = file_exists($classMapFile) ? require $classMapFile : [];

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
}
