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
    /**
     * The namespace that contains all classes.
     *
     * @var string
     */
    private $classNamespace;

    private $useStatements = [];

    private $traits = [];

    private $implements = [];

    private $skeletonDirs = null;

    /**
     * Number of spaces to use for indention in generated code.
     */
    private $numSpaces = 4;

    /**
     * The actual spaces to use for indention.
     *
     * @var string
     */
    private $spaces = '    ';

    private static $templates = [];

    /**
     * @param string $classNamespace The namespace to use for the classes.
     * @param string|null $skeletonDirs
     */
    public function __construct($classNamespace = null, $skeletonDirs = null)
    {
        $this->setClassNamespace($classNamespace);
        $this->setSkeletonDirs($skeletonDirs);
    }

    public function getClassNamespace()
    {
        return $this->classNamespace;
    }

    public function setClassNamespace($classNamespace)
    {
        $this->classNamespace = $classNamespace;

        return $this;
    }

    public function setSkeletonDirs($skeletonDirs = null)
    {
        if (null === $skeletonDirs) {
            $this->skeletonDirs = __DIR__ . '/../Resources/skeleton';
        } else {
            if (!is_dir($skeletonDirs)) {
                throw new \InvalidArgumentException(sprintf('Skeleton dir "%s" not found.', $skeletonDirs));
            }
            $this->skeletonDirs = realpath($skeletonDirs);
        }

        return $this;
    }

    /**
     * Sets the number of spaces the exported class should have.
     *
     * @param integer $numSpaces
     *
     * @return void
     */
    public function setNumSpaces($numSpaces)
    {
        $this->spaces = str_repeat(' ', $numSpaces);
        $this->numSpaces = $numSpaces;
    }

    public function addTrait($trait)
    {
        $cleanTrait = $this->shortenClassName($trait);
        if (!in_array($cleanTrait, $this->traits)) {
            $this->traits[] = $cleanTrait;
        }
    }

    public function addImplement($implement)
    {
        $cleanImplement = $this->shortenClassName($implement);
        if (!in_array($cleanImplement, $this->implements)) {
            $this->implements[] = $cleanImplement;
        }
    }

    public function getSkeletonContent($skeleton)
    {
        $path = $this->skeletonDirs . '/' . $skeleton;

        if (!isset(self::$templates[$path])) {
            if (!file_exists($path)) {
                throw new \InvalidArgumentException(sprintf('Template "%s" not found.', $path));
            }

            $content = trim(file_get_contents($path));

            self::$templates[$path] = $content;
        }

        return self::$templates[$path];
    }

    protected function shortenClassName($definition)
    {
        $shortName = ClassUtils::shortenClassName($definition);
        $this->addUseStatement(preg_replace('@\:\:.*$@i', '', $definition));

        return $shortName;
    }

    protected function shortenClassFromCode($code)
    {
        $codeParsed = ClassUtils::shortenClassFromCode(
            $code,
            function ($matches) {
                return $this->shortenClassFromCode($matches[1]);
            }
        );

        return $codeParsed;
    }

    protected function resetUseStatements()
    {
        $this->useStatements = [];
    }

    protected function addUseStatement($use)
    {
        $cleanUse = ltrim($use, '\\');
        if (!in_array($cleanUse, $this->useStatements)) {
            $this->useStatements[] = $cleanUse;
        }
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

    protected function generateNamespace()
    {
        if (null !== $this->classNamespace) {
            return 'namespace ' . $this->classNamespace . ';';
        }
    }

    protected function generateUseStatement(array $config)
    {
        $useStatements = $this->tokenizeUseStatements($this->useStatements);
        $this->resetUseStatements();

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

    /**
     * Generates classes files.
     *
     * @param array $configs raw configs
     * @param string $outputDirectory
     * @param bool $regenerateIfExists
     *
     * @return array classes map [[FQCLN => classPath], [FQCLN => classPath], ...]
     */
    abstract public function generateClasses(array $configs, $outputDirectory, $regenerateIfExists = false);

    abstract public function generateClass(array $config, $outputDirectory, $regenerateIfExists = false);
}
