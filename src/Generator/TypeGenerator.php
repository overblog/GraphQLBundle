<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator;

use Composer\Autoload\ClassLoader;
use Overblog\GraphQLBundle\Config\Processor;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\Type\CustomScalarType;
use Overblog\GraphQLGenerator\Generator\TypeGenerator as BaseTypeGenerator;
use Symfony\Component\Filesystem\Filesystem;

class TypeGenerator extends BaseTypeGenerator
{
    public const USE_FOR_CLOSURES = '$globalVariable';

    public const DEFAULT_CONFIG_PROCESSOR = [Processor::class, 'process'];

    private $cacheDir;

    private $configProcessor;

    private $configs;

    private $useClassMap;

    private $baseCacheDir;

    private static $classMapLoaded = false;

    public function __construct(
        $classNamespace,
        array $skeletonDirs,
        $cacheDir,
        array $configs,
        $useClassMap = true,
        callable $configProcessor = null,
        $baseCacheDir = null
    ) {
        $this->setCacheDir($cacheDir);
        $this->configProcessor = null === $configProcessor ? static::DEFAULT_CONFIG_PROCESSOR : $configProcessor;
        $this->configs = $configs;
        $this->useClassMap = $useClassMap;
        $this->baseCacheDir = $baseCacheDir;

        parent::__construct($classNamespace, $skeletonDirs);
    }

    /**
     * @return string|null
     */
    public function getBaseCacheDir()
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

    /**
     * @return string|null
     */
    public function getCacheDir(/*bool $useDefault = true*/)
    {
        $useDefault = \func_num_args() > 0 ? \func_get_arg(0) : true;
        if ($useDefault) {
            return $this->cacheDir ?: $this->baseCacheDir.'/overblog/graphql-bundle/__definitions__';
        } else {
            return $this->cacheDir;
        }
    }

    /**
     * @param string|null $cacheDir
     *
     * @return $this
     */
    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = $cacheDir;

        return $this;
    }

    protected function generateClassName(array $config)
    {
        return $config['class_name'];
    }

    protected function generateClassDocBlock(array $value)
    {
        return <<<'EOF'

/**
 * THIS FILE WAS GENERATED AND SHOULD NOT BE MODIFIED!
 */
EOF;
    }

    protected function generateClosureUseStatements(array $config)
    {
        return \sprintf('use (%s) ', static::USE_FOR_CLOSURES);
    }

    protected function resolveTypeCode($alias)
    {
        return  \sprintf('$globalVariable->get(\'typeResolver\')->resolve(%s)', \var_export($alias, true));
    }

    protected function generatePublic(array $value)
    {
        if (!$this->arrayKeyExistsAndIsNotNull($value, 'public')) {
            return 'null';
        }

        $publicCallback = $this->callableCallbackFromArrayValue($value, 'public', '$typeName, $fieldName');

        $code = <<<'CODE'
function ($fieldName) <closureUseStatements> {
<spaces><spaces>$publicCallback = %s;
<spaces><spaces>return call_user_func($publicCallback, $this->name, $fieldName);
<spaces>}
CODE;

        $code = \sprintf($code, $publicCallback);

        return $code;
    }

    protected function generateAccess(array $value)
    {
        if (!$this->arrayKeyExistsAndIsNotNull($value, 'access')) {
            return 'null';
        }

        if (\is_bool($value['access'])) {
            return $this->varExport($value['access']);
        }

        return $this->callableCallbackFromArrayValue($value, 'access', '$value, $args, $context, \\GraphQL\\Type\\Definition\\ResolveInfo $info, $object');
    }

    /**
     * @param array $value
     *
     * @return string
     */
    protected function generateComplexity(array $value)
    {
        $resolveComplexity = parent::generateComplexity($value);
        $resolveComplexity = \ltrim($this->prefixCodeWithSpaces($resolveComplexity));

        if ('null' === $resolveComplexity) {
            return $resolveComplexity;
        }

        $argumentClass = $this->shortenClassName(Argument::class);

        $code = <<<'CODE'
function ($childrenComplexity, $args = []) <closureUseStatements>{
<spaces><spaces>$resolveComplexity = %s;

<spaces><spaces>return call_user_func_array($resolveComplexity, [$childrenComplexity, new %s($args)]);
<spaces>}
CODE;

        $code = \sprintf($code, $resolveComplexity, $argumentClass);

        return $code;
    }

    /**
     * @param array $value
     *
     * @return string
     */
    protected function generateScalarType(array $value)
    {
        return $this->callableCallbackFromArrayValue($value, 'scalarType');
    }

    protected function generateParentClassName(array $config)
    {
        if ('custom-scalar' === $config['type']) {
            return $this->shortenClassName(CustomScalarType::class);
        } else {
            return parent::generateParentClassName($config);
        }
    }

    protected function generateTypeName(array $config)
    {
        return $this->varExport($config['config']['name']);
    }

    public function compile($mode)
    {
        $cacheDir = $this->getCacheDir();
        $writeMode = $mode & self::MODE_WRITE;
        if ($writeMode && \file_exists($cacheDir)) {
            $fs = new Filesystem();
            $fs->remove($cacheDir);
        }
        $configs = \call_user_func($this->configProcessor, $this->configs);
        $classes = $this->generateClasses($configs, $cacheDir, $mode);

        if ($writeMode && $this->useClassMap) {
            $content = "<?php\nreturn ".\var_export($classes, true).';';
            // replaced hard-coding absolute path by __DIR__ (see https://github.com/overblog/GraphQLBundle/issues/167)
            $content = \str_replace(' => \''.$cacheDir, ' => __DIR__ . \'', $content);

            \file_put_contents($this->getClassesMap(), $content);

            $this->loadClasses(true);
        }

        return $classes;
    }

    public function loadClasses($forceReload = false): void
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

    private function getClassesMap()
    {
        return $this->getCacheDir().'/__classes.map';
    }
}
