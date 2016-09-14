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

use Overblog\GraphQLGenerator\Generator\TypeGenerator as BaseTypeGenerator;
use Symfony\Component\ClassLoader\MapClassLoader;
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
        return <<<EOF

/**
 * THIS FIELD WAS GENERATED AND SHOULD NOT BE MODIFIED!
 */
EOF;
    }

    protected function generateClosureUseStatements(array $config)
    {
        return 'use ('.static::USE_FOR_CLOSURES.') ';
    }

    protected function resolveTypeCode($alias)
    {
        return  sprintf('$container->get(\'%s\')->resolve(%s)', 'overblog_graphql.type_resolver', var_export($alias, true));
    }

    /**
     * todo replace generateResolve in vendor after spec-april2016 is merged
     *
     * @param array $value
     * @return string
     */
    protected function generateResolve2(array $value)
    {
        return $this->callableCallbackFromArrayValue($value, 'resolve', '$value, $args, $context, \\GraphQL\\Type\\Definition\\ResolveInfo $info');
    }

    protected function generateResolve(array $value)
    {
        $accessIsSet = $this->arrayKeyExistsAndIsNotNull($value, 'access');
        $fieldOptions = $value;
        $fieldOptions['resolve'] = $this->arrayKeyExistsAndIsNotNull($fieldOptions, 'resolve') ? $fieldOptions['resolve'] : $this->defaultResolver;

        if (!$accessIsSet || true === $fieldOptions['access']) { // access granted  to this field
            $resolveCallback = $this->generateResolve2($fieldOptions);
            if ('null' === $resolveCallback) {
                return $resolveCallback;
            }

            $argumentClass = $this->shortenClassName('\\Overblog\\GraphQLBundle\\Definition\\Argument');
            $resolveInfoClass = $this->shortenClassName('\\GraphQL\\Type\\Definition\\ResolveInfo');

            $code = <<<'CODE'
function ($value, $args, $context, %s $info) <closureUseStatements> {
<spaces><spaces>$resolverCallback = %s;

<spaces><spaces>return call_user_func_array($resolverCallback, [$value, new %s($args), $context, $info]);
<spaces>}
CODE;

            return sprintf($code, $resolveInfoClass, $resolveCallback, $argumentClass);
        } elseif ($accessIsSet && false === $fieldOptions['access']) { // access deny to this field
            $exceptionClass = $this->shortenClassName('\\Overblog\\GraphQLBundle\\Error\\UserWarning');

            return sprintf('function () { throw new %s(\'Access denied to this field.\'); }', $exceptionClass);
        } else { // wrap resolver with access
            $resolveCallback = $this->generateResolve2($fieldOptions);
            $accessChecker = $this->callableCallbackFromArrayValue($fieldOptions, 'access', '$value, $args, $context, \\GraphQL\\Type\\Definition\\ResolveInfo $info, $object');
            $resolveInfoClass = $this->shortenClassName('\\GraphQL\\Type\\Definition\\ResolveInfo');
            $argumentClass = $this->shortenClassName('\\Overblog\\GraphQLBundle\\Definition\\Argument');

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

        if ('null' === $resolveComplexity) {
            return $resolveComplexity;
        }

        $argumentClass = $this->shortenClassName('\\Overblog\\GraphQLBundle\\Definition\\Argument');

        $code = <<<'CODE'
function ($childrenComplexity, $args = []) <closureUseStatements> {
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
        file_put_contents($this->getClassesMap(), "<?php\nreturn ".var_export($classes, true).';');

        if ($loadClasses) {
            $this->loadClasses(true);
        }

        return $classes;
    }

    public function loadClasses($forceReload = false)
    {
        if (!self::$classMapLoaded || $forceReload) {
            $classes = require $this->getClassesMap();

            $mapClassLoader = new MapClassLoader($classes);
            $mapClassLoader->register();

            self::$classMapLoaded = true;
        }
    }

    private function getClassesMap()
    {
        return $this->getCacheDir().'/__classes.map';
    }
}
