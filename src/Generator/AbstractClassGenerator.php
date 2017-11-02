<?php

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
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

abstract class AbstractClassGenerator
{
    const SKELETON_FILE_PREFIX = '.php.skeleton';

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

    public function setClassNamespace($classNamespace)
    {
        $this->classNamespace = ClassUtils::cleanClasseName($classNamespace);

        return $this;
    }

    /**
     * @param string[]|string $skeletonDirs
     * @return $this
     */
    public function setSkeletonDirs($skeletonDirs)
    {
        $this->skeletonDirs = [];

        if (is_string($skeletonDirs)) {
            $this->addSkeletonDir($skeletonDirs);
        } else {
            if (!is_array($skeletonDirs) && !$skeletonDirs instanceof \Traversable) {
                throw new \InvalidArgumentException(
                    sprintf('Skeleton dirs must be array or object implementing \Traversable interface, "%s" given.', gettype($skeletonDirs))
                );
            }

            foreach ($skeletonDirs as $skeletonDir) {
                $this->addSkeletonDir($skeletonDir);
            }
        }

        return $this;
    }

    public function getSkeletonDirs($withDefault = true)
    {
        $skeletonDirs = $this->skeletonDirs ;

        if ($withDefault) {
            $skeletonDirs[] =  __DIR__ . '/../Resources/skeleton';
        }

        return $skeletonDirs;
    }

    public function addSkeletonDir($skeletonDir)
    {
        if (!is_string($skeletonDir) && !is_object($skeletonDir) && !is_callable($skeletonDir, '__toString')) {
            throw new \InvalidArgumentException(
                sprintf('Skeleton dir must be string or object implementing __toString, "%s" given.', gettype($skeletonDir))
            );
        }

        $skeletonDir = (string) $skeletonDir;

        if (!is_dir($skeletonDir)) {
            throw new \InvalidArgumentException(sprintf('Skeleton dir "%s" not found.', $skeletonDir));
        }
        $this->skeletonDirs[] = realpath($skeletonDir);

        return $this;
    }


    /**
     * Sets the number of spaces the exported class should have.
     *
     * @param integer $numSpaces
     *
     * @return self
     */
    public function setNumSpaces($numSpaces)
    {
        $this->spaces = str_repeat(' ', $numSpaces);
        $this->numSpaces = $numSpaces;

        return $this;
    }

    public function addTrait($trait)
    {
        $cleanTrait = $this->shortenClassName($trait, false);
        if (!in_array($cleanTrait, $this->traits)) {
            $this->traits[] = $cleanTrait;
        }

        return $this;
    }

    public function clearTraits()
    {
        $this->traits = [];

        return $this;
    }

    public function addImplement($implement)
    {
        $cleanImplement = $this->shortenClassName($implement, false);
        if (!in_array($cleanImplement, $this->implements)) {
            $this->implements[] = $cleanImplement;
        }

        return $this;
    }

    public function clearImplements()
    {
        $this->implements = [];

        return $this;
    }

    public function addUseStatement($useStatement)
    {
        $cleanUse = ClassUtils::cleanClasseName($useStatement);
        if (!in_array($cleanUse, $this->useStatements)) {
            $this->useStatements[] = $cleanUse;
        }

        return $this;
    }

    public function clearUseStatements()
    {
        $this->useStatements = [];

        return $this;
    }

    public function getSkeletonContent($skeleton, $withDefault = true)
    {
        $skeletonDirs = $this->getSkeletonDirs($withDefault);

        foreach ($skeletonDirs as $skeletonDir) {
            $path = $skeletonDir . '/' . $skeleton . static::SKELETON_FILE_PREFIX;

            if (!file_exists($path)) {
                continue;
            }

            if (!isset(self::$templates[$path])) {
                $content = trim(file_get_contents($path));

                self::$templates[$path] = $content;
            }

            return self::$templates[$path];
        }

        throw new \InvalidArgumentException(
            sprintf(
                'Skeleton "%s" could not be found in %s.',
                $skeleton,
                implode(', ', $skeletonDirs)
            )
        );
    }

    protected function addInternalUseStatement($use)
    {
        $cleanUse = ClassUtils::cleanClasseName($use);
        if (!in_array($cleanUse, $this->internalUseStatements)) {
            $this->internalUseStatements[] = $cleanUse;
        }
    }

    protected function clearInternalUseStatements()
    {
        $this->internalUseStatements = [];

        return $this;
    }

    protected function shortenClassName($definition, $isInternal = true)
    {
        $shortName = ClassUtils::shortenClassName($definition);

        $useStatement = preg_replace('@\:\:.*$@i', '', $definition);
        if ($isInternal) {
            $this->addInternalUseStatement($useStatement);
        } else {
            $this->addUseStatement($useStatement);
        }

        return $shortName;
    }

    protected function shortenClassFromCode($code)
    {
        $codeParsed = ClassUtils::shortenClassFromCode(
            $code,
            function ($matches) {
                return $this->shortenClassName($matches[1]);
            }
        );

        return $codeParsed;
    }

    protected function processPlaceHoldersReplacements(array $placeHolders, $content, array $values)
    {
        $replacements = [];

        foreach ($placeHolders as $placeHolder) {
            $generator = [$this, 'generate' . ucfirst($placeHolder)];
            $name = '<' . $placeHolder . '>';

            if (is_callable($generator)) {
                $replacements[$name] = call_user_func_array($generator, [$values]);
            } else {
                throw new \RuntimeException(
                    sprintf(
                        'Generator [%s] for placeholder "%s" is not callable.',
                        get_class($generator[0]) . '::' . $generator[1],
                        $placeHolder
                    )
                );
            }
        }

        return strtr($content, $replacements);
    }

    protected function processTemplatePlaceHoldersReplacements($template, array $values, array $skip = [])
    {
        $code = $this->getSkeletonContent($template);
        $placeHolders = $this->getPlaceHolders($code);
        $code = $this->processPlaceHoldersReplacements(array_diff($placeHolders, $skip), $code, $values);

        return $code;
    }

    protected function getPlaceHolders($content)
    {
        preg_match_all('@<([\w]+)>@i', $content, $placeHolders);

        return isset($placeHolders[1]) ? $placeHolders[1] : [];
    }

    /**
     * @param string $code
     * @param int $num
     *
     * @return string
     */
    protected function prefixCodeWithSpaces($code, $num = 1)
    {
        $lines = explode("\n", $code);

        foreach ($lines as $key => $value) {
            if (!empty($value)) {
                $lines[$key] = str_repeat($this->spaces, $num) . $lines[$key];
            }
        }

        return implode("\n", $lines);
    }

    protected function generateSpaces()
    {
        return $this->spaces;
    }

    protected function generateNamespace()
    {
        return null !== $this->classNamespace ? 'namespace ' . $this->classNamespace . ';' : null;
    }

    protected function generateUseStatement(array $config)
    {
        $useStatements = $this->tokenizeUseStatements(array_merge($this->internalUseStatements, $this->useStatements));

        return $useStatements;
    }

    protected function generateClassType()
    {
        return 'final ';
    }

    protected function generateImplements()
    {
        return count($this->implements) ? ' implements ' . implode(', ', $this->implements) : null;
    }

    protected function generateTraits()
    {
        $traits = $this->tokenizeUseStatements($this->traits, '<spaces>');

        return $traits ? $traits . "\n" : $traits;
    }

    protected function tokenizeUseStatements(array $useStatements, $prefix = '')
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
     * @param int|bool $mode
     *
     * @return array classes map [[FQCLN => classPath], [FQCLN => classPath], ...]
     */
    abstract public function generateClasses(array $configs, $outputDirectory, $mode = false);

    /**
     * Generates a class file.
     *
     * @param array $config
     * @param $outputDirectory
     * @param bool $mode
     *
     * @return array classes map [FQCLN => classPath]
     */
    abstract public function generateClass(array $config, $outputDirectory, $mode = false);
}
