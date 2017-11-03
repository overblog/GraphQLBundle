<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Generator;

use Composer\Autoload\ClassLoader;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Error\UserWarning;
use Overblog\GraphQLGenerator\Generator\TypeGenerator as BaseTypeGenerator;
use Symfony\Component\Filesystem\Filesystem;

class TypeGenerator extends BaseTypeGenerator
{
    const USE_FOR_CLOSURES = '$container, $request, $user, $token';

    private $cacheDir;

    private $defaultResolver;

    private static $classMapLoaded = false;

    public function __construct($classNamespace, array $skeletonDirs, $cacheDir, callable $defaultResolver)
    {
        $this->setCacheDir($cacheDir);
        $this->defaultResolver = $defaultResolver;
        parent::__construct($classNamespace, $skeletonDirs);
    }

    /**
     * @return string
     */
    public function getCacheDir()
    {
        return $this->cacheDir;
    }

    /**
     * @param string $cacheDir
     *
     * @return $this
     */
    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = $cacheDir;

        return $this;
    }

    protected function generateClassDocBlock(array $value)
    {
        return <<<'EOF'

/**
 * THIS FILE WAS GENERATED AND SHOULD NOT BE MODIFIED!
 */
EOF;
    }

    protected function generateOutputFields(array $config)
    {
        $outputFieldsCode = sprintf(
            'self::applyPublicFilters(%s)',
            $this->processFromArray($config['fields'], 'OutputField')
        );

        return sprintf(static::$closureTemplate, '', $outputFieldsCode);
    }

    protected function generateClosureUseStatements(array $config)
    {
        return 'use ('.static::USE_FOR_CLOSURES.') ';
    }

    protected function resolveTypeCode($alias)
    {
        return  sprintf('$container->get(\'%s\')->resolve(%s)', 'overblog_graphql.type_resolver', var_export($alias, true));
    }

    protected function generatePublic(array $value)
    {
        if (!$this->arrayKeyExistsAndIsNotNull($value, 'public')) {
            return 'null';
        }

        $publicCallback = $this->callableCallbackFromArrayValue($value, 'public', '$typeName, $fieldName');

        if ('null' === $publicCallback) {
            return $publicCallback;
        }

        $code = <<<'CODE'
function ($fieldName) <closureUseStatements> {
<spaces><spaces>$publicCallback = %s;
<spaces><spaces>return call_user_func($publicCallback, $this->name, $fieldName);
<spaces>}
CODE;

        $code = sprintf($code, $publicCallback);

        return $code;
    }

    protected function generateResolve(array $value)
    {
        $accessIsSet = $this->arrayKeyExistsAndIsNotNull($value, 'access');
        $fieldOptions = $value;
        if (!$this->arrayKeyExistsAndIsNotNull($fieldOptions, 'resolve')) {
            $fieldOptions['resolve'] = $this->defaultResolver;
        }
        $resolveCallback = parent::generateResolve($fieldOptions);
        $resolveCallback = ltrim($this->prefixCodeWithSpaces($resolveCallback));

        if (!$accessIsSet || true === $fieldOptions['access']) { // access granted  to this field
            if ('null' === $resolveCallback) {
                return $resolveCallback;
            }

            $argumentClass = $this->shortenClassName(Argument::class);
            $resolveInfoClass = $this->shortenClassName(ResolveInfo::class);

            $code = <<<'CODE'
function ($value, $args, $context, %s $info) <closureUseStatements>{
<spaces><spaces>$resolverCallback = %s;
<spaces><spaces>return call_user_func_array($resolverCallback, [$value, new %s($args), $context, $info]);
<spaces>}
CODE;

            return sprintf($code, $resolveInfoClass, $resolveCallback, $argumentClass);
        } elseif ($accessIsSet && false === $fieldOptions['access']) { // access deny to this field
            $exceptionClass = $this->shortenClassName(UserWarning::class);

            return sprintf('function () { throw new %s(\'Access denied to this field.\'); }', $exceptionClass);
        } else { // wrap resolver with access

            $accessChecker = $this->callableCallbackFromArrayValue($fieldOptions, 'access', '$value, $args, $context, '.ResolveInfo::class.' $info, $object');
            $resolveInfoClass = $this->shortenClassName(ResolveInfo::class);
            $argumentClass = $this->shortenClassName(Argument::class);

            $code = <<<'CODE'
function ($value, $args, $context, %s $info) <closureUseStatements> {
<spaces><spaces>$resolverCallback = %s;
<spaces><spaces>$accessChecker = %s;
<spaces><spaces>$isMutation = $info instanceof ResolveInfo && 'mutation' === $info->operation->operation && $info->parentType === $info->schema->getMutationType();
<spaces><spaces>return $container->get('overblog_graphql.access_resolver')->resolve($accessChecker, $resolverCallback, [$value, new %s($args), $context, $info], $isMutation);
<spaces>}
CODE;

            $code = sprintf($code, $resolveInfoClass, $resolveCallback, $accessChecker, $argumentClass);

            return $code;
        }
    }

    /**
     * @param array $value
     *
     * @return string
     */
    protected function generateComplexity(array $value)
    {
        $resolveComplexity = parent::generateComplexity($value);
        $resolveComplexity = ltrim($this->prefixCodeWithSpaces($resolveComplexity));

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

        $code = sprintf($code, $resolveComplexity, $argumentClass);

        return $code;
    }

    public function compile(array $configs, $loadClasses = true)
    {
        $cacheDir = $this->getCacheDir();
        if (file_exists($cacheDir)) {
            $fs = new Filesystem();
            $fs->remove($cacheDir);
        }

        $classes = $this->generateClasses($configs, $cacheDir, true);

        if ($loadClasses) {
            $content = "<?php\nreturn ".var_export($classes, true).';';
            // replaced hard-coding absolute path by __DIR__ (see https://github.com/overblog/GraphQLBundle/issues/167)
            $content = str_replace(' => \''.$cacheDir, ' => __DIR__ . \'', $content);

            file_put_contents($this->getClassesMap(), $content);

            $this->loadClasses(true);
        }

        return $classes;
    }

    public function loadClasses($forceReload = false)
    {
        if (!self::$classMapLoaded || $forceReload) {
            $classes = require $this->getClassesMap();
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
