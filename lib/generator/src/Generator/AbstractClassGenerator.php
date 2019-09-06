<?php declare(strict_types=1);

/*
 * This file is part of the OverblogGraphQLPhpGenerator package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLGenerator\Generator;

use Overblog\GraphQLGenerator\ClassUtils;

abstract class AbstractClassGenerator
{
    public const MODE_DRY_RUN = 1;
    public const MODE_MAPPING_ONLY = 2;
    public const MODE_WRITE = 4;
    public const MODE_OVERRIDE = 8;

    protected const SKELETON_FILE_PREFIX = '.php.skeleton';

    /**
     * The namespace that contains all classes.
     *
     * @var string
     */
    private $classNamespace;

    private $internalUseStatements = [];

    private $useStatements = [];

    private $traits = [];

    private $implements = [];

    private $skeletonDirs = [];

    /**
     * Number of spaces to use for indention in generated code.
     */
    private $numSpaces;

    /**
     * The actual spaces to use for indention.
     *
     * @var string
     */
    private $spaces;

    private static $templates = [];

    /**
     * @param string $classNamespace The namespace to use for the classes.
     * @param string[]|string $skeletonDirs
     */
    public function __construct($classNamespace = null, $skeletonDirs = [])
    {
        $this->setClassNamespace($classNamespace);
        $this->setSkeletonDirs($skeletonDirs);
        $this->setNumSpaces(4);
    }

    public function getClassNamespace()
    {
        return $this->classNamespace;
    }

    public function setClassNamespace($classNamespace): self
    {
        $this->classNamespace = ClassUtils::cleanClasseName($classNamespace);

        return $this;
    }

    public function setSkeletonDirs($skeletonDirs): self
    {
        $this->skeletonDirs = [];

        if (\is_string($skeletonDirs)) {
            $this->addSkeletonDir($skeletonDirs);
        } else {
            if (!\is_array($skeletonDirs) && !$skeletonDirs instanceof \Traversable) {
                throw new \InvalidArgumentException(
                    \sprintf('Skeleton dirs must be array or object implementing \Traversable interface, "%s" given.', \gettype($skeletonDirs))
                );
            }

            foreach ($skeletonDirs as $skeletonDir) {
                $this->addSkeletonDir($skeletonDir);
            }
        }

        return $this;
    }

    public function getSkeletonDirs(bool $withDefault = true): array
    {
        $skeletonDirs = $this->skeletonDirs ;

        if ($withDefault) {
            $skeletonDirs[] = __DIR__.'/../Resources/skeleton';
        }

        return $skeletonDirs;
    }

    public function addSkeletonDir($skeletonDir): self
    {
        if (!\is_string($skeletonDir) && !\is_object($skeletonDir) && !\is_callable([$skeletonDir, '__toString'])) {
            throw new \InvalidArgumentException(
                \sprintf('Skeleton dir must be string or object implementing __toString, "%s" given.', \gettype($skeletonDir))
            );
        }

        $skeletonDir = (string) $skeletonDir;

        if (!\is_dir($skeletonDir)) {
            throw new \InvalidArgumentException(\sprintf('Skeleton dir "%s" not found.', $skeletonDir));
        }
        $this->skeletonDirs[] = \realpath($skeletonDir);

        return $this;
    }


    /**
     * Sets the number of spaces the exported class should have.
     *
     * @param integer $numSpaces
     *
     * @return self
     */
    public function setNumSpaces(int $numSpaces): self
    {
        $this->spaces = \str_repeat(' ', $numSpaces);
        $this->numSpaces = $numSpaces;

        return $this;
    }

    public function addTrait(string $trait): self
    {
        $cleanTrait = $this->shortenClassName($trait, false);
        if (!\in_array($cleanTrait, $this->traits)) {
            $this->traits[] = $cleanTrait;
        }

        return $this;
    }

    public function clearTraits(): self
    {
        $this->traits = [];

        return $this;
    }

    public function addImplement(string $implement): self
    {
        $cleanImplement = $this->shortenClassName($implement, false);
        if (!\in_array($cleanImplement, $this->implements)) {
            $this->implements[] = $cleanImplement;
        }

        return $this;
    }

    public function clearImplements(): self
    {
        $this->implements = [];

        return $this;
    }

    public function addUseStatement(string $useStatement): self
    {
        $cleanUse = ClassUtils::cleanClasseName($useStatement);
        if (!\in_array($cleanUse, $this->useStatements)) {
            $this->useStatements[] = $cleanUse;
        }

        return $this;
    }

    public function clearUseStatements(): self
    {
        $this->useStatements = [];

        return $this;
    }

    public function getSkeletonContent(string $skeleton, bool $withDefault = true)
    {
        $skeletonDirs = $this->getSkeletonDirs($withDefault);

        foreach ($skeletonDirs as $skeletonDir) {
            $path = $skeletonDir.'/'.$skeleton.static::SKELETON_FILE_PREFIX;

            if (!\file_exists($path)) {
                continue;
            }

            if (!isset(self::$templates[$path])) {
                $content = \trim(\file_get_contents($path));

                self::$templates[$path] = $content;
            }

            return self::$templates[$path];
        }

        throw new \InvalidArgumentException(
            \sprintf(
                'Skeleton "%s" could not be found in %s.',
                $skeleton,
                \implode(', ', $skeletonDirs)
            )
        );
    }

    protected function addInternalUseStatement(string $use): void
    {
        $cleanUse = ClassUtils::cleanClasseName($use);
        if (!\in_array($cleanUse, $this->internalUseStatements)) {
            $this->internalUseStatements[] = $cleanUse;
        }
    }

    protected function clearInternalUseStatements(): self
    {
        $this->internalUseStatements = [];

        return $this;
    }

    protected function shortenClassName(string $definition, bool $isInternal = true): string
    {
        $shortName = ClassUtils::shortenClassName($definition);

        $useStatement = \preg_replace('@\:\:.*$@i', '', $definition);
        if ($isInternal) {
            $this->addInternalUseStatement($useStatement);
        } else {
            $this->addUseStatement($useStatement);
        }

        return $shortName;
    }

    protected function shortenClassFromCode(?string $code): string
    {
        $codeParsed = ClassUtils::shortenClassFromCode(
            $code,
            function ($matches) {
                return $this->shortenClassName($matches[1]);
            }
        );

        return $codeParsed;
    }

    protected function processPlaceHoldersReplacements(array $placeHolders, string $content, array $values): string
    {
        $replacements = [];

        foreach ($placeHolders as $placeHolder) {
            $generator = [$this, 'generate'.\ucfirst($placeHolder)];
            $name = '<'.$placeHolder.'>';

            if (\is_callable($generator)) {
                $replacements[$name] = \call_user_func_array($generator, [$values]);
            } else {
                throw new \RuntimeException(
                    \sprintf(
                        'Generator [%s] for placeholder "%s" is not callable.',
                        \get_class($generator[0]).'::'.$generator[1],
                        $placeHolder
                    )
                );
            }
        }

        return \strtr($content, $replacements);
    }

    protected function processTemplatePlaceHoldersReplacements(string $template, array $values, array $skip = []): string
    {
        $code = $this->getSkeletonContent($template);
        $placeHolders = $this->getPlaceHolders($code);
        $code = $this->processPlaceHoldersReplacements(\array_diff($placeHolders, $skip), $code, $values);

        return $code;
    }

    protected function getPlaceHolders(string $content): array
    {
        \preg_match_all('@<([\w]+)>@i', $content, $placeHolders);

        return $placeHolders[1] ?? [];
    }

    /**
     * @param string $code      Code to prefix
     * @param int    $num       Number of indents
     * @param bool   $skipFirst Skip first line
     *
     * @return string
     */
    protected function prefixCodeWithSpaces(string $code, int $num = 1, $skipFirst = true): string
    {
        $lines = \explode("\n", $code);

        foreach ($lines as $key => $value) {
            if (!empty($value)) {
                $lines[$key] = \str_repeat($this->spaces, $num).$lines[$key];
            }
        }

        if ($skipFirst) {
            $lines[0] = ltrim($lines[0]);
        }

        return \implode("\n", $lines);
    }

    protected function generateSpaces(): string
    {
        return $this->spaces;
    }

    protected function generateNamespace(): ?string
    {
        return null !== $this->classNamespace ? 'namespace '.$this->classNamespace.';' : null;
    }

    protected function generateUseStatement(array $config): string
    {
        $statements = \array_merge($this->internalUseStatements, $this->useStatements);
        \sort($statements);

        $useStatements = $this->tokenizeUseStatements($statements);

        return $useStatements;
    }

    protected function generateClassType(): string
    {
        return 'final ';
    }

    protected function generateImplements(): ?string
    {
        return \count($this->implements) ? ' implements '.\implode(', ', $this->implements) : null;
    }

    protected function generateTraits(): ?string
    {
        $traits = $this->tokenizeUseStatements($this->traits, '<spaces>');

        return $traits ? $traits."\n" : $traits;
    }

    protected function tokenizeUseStatements(array $useStatements, $prefix = ''): ?string
    {
        if (empty($useStatements)) {
            return null;
        }

        $code = '';

        foreach ($useStatements as $useStatement) {
            $code .= "\n${prefix}use $useStatement;";
        }

        return $code;
    }

    /**
     * Generates classes files.
     *
     * @param array    $configs raw configs
     * @param string   $outputDirectory
     * @param int $mode
     *
     * @return array classes map [[FQCLN => classPath], [FQCLN => classPath], ...]
     */
    abstract public function generateClasses(array $configs, ?string $outputDirectory, int $mode = self::MODE_WRITE): array;

    /**
     * Generates a class file.
     *
     * @param array  $config
     * @param string $outputDirectory
     * @param int    $mode
     *
     * @return array classes map [FQCLN => classPath]
     */
    abstract public function generateClass(array $config, ?string $outputDirectory, int $mode = self::MODE_WRITE): array;
}
