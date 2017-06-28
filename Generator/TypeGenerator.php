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
    const USE_FOR_CLOSURES = '$container';

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

    protected function generateClosureUseStatements(array $config)
    {
        return 'use ('.static::USE_FOR_CLOSURES.') ';
    }

    protected function resolveTypeCode($alias)
    {
        return  sprintf('$container->get(\'%s\')->resolve(%s)', 'overblog_graphql.type_resolver', var_export($alias, true));
    }

    protected function generateResolve(array $value)
    {
        $accessIsSet = $this->arrayKeyExistsAndIsNotNull($value, 'access');
        $fieldOptions = $value;
        if (!$this->arrayKeyExistsAndIsNotNull($fieldOptions, 'resolve')) {
            $fieldOptions['resolve'] = $this->defaultResolver;
        }
        $resolveCallback = parent::generateResolve($fieldOptions);

        if (!$accessIsSet || true === $fieldOptions['access']) { // access granted  to this field
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

        $configs = $this->substituteEnumNamesInDefaultValues($configs);
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

    /**
     * Substitute enum value's names with enum value's values in all relevant defaultValue.
     *
     * Longer explanation follows.
     *
     * First, let's establish a terminology shorthand:
     * enum value's name = e_name.
     * enum value's value = e_value.
     *
     * So, type definitions in GraphQL schema language have no knowledge of custom e_values. They will therefore
     * always use e_names for field argument default values.
     *
     * Problem: in cases when e_name != e_value, webonyx/graphql-php library requires e_values in defaultValue.
     *
     * Solution: translate e_names into e_values during class generation.
     *
     * Since the types in need of conversion could have been defined in a different graphqls file than the relevant
     * enums, this class is the first place where both definitions are guaranteed to exist side-by-side,
     * arg default values can be checked for whether they contain e_names, and if they do, converted into e_values.
     *
     * @param array $configs
     * @throws \Exception If someone was very silly indeed and mixed e_names and e_values in defaultValue
     */
    protected function substituteEnumNamesInDefaultValues($configs)
    {
        // generate a lookup table
        $enumConfigsByName = [];

        foreach ($configs as $classConfig) {
            if ($classConfig['type'] == 'enum') {
                $name = $classConfig['config']['name'];
                $enumConfigsByName[$name] = $classConfig;
            }
        }

        // check each type config for fields which have arguments of an enum type, and also have a defaultValue

        // first, check each type config for field arguments that have a defaultValue (cheaper check first)
        foreach ($configs as &$classConfig) {
            if (isset($classConfig['config']['fields'])) {
                foreach ($classConfig['config']['fields'] as &$fieldConfig) {
                    if (isset($fieldConfig['args'])) {
                        foreach ($fieldConfig['args'] as &$argConfig) {
                            if (isset($argConfig['defaultValue'])) {

                                // this argument has a default value, now perform the (more expensive) type check
                                $type = $argConfig['type'];
                                $type = ('[' === $type[0]) ? substr($type, 1, -1) : $type;

                                if (isset($enumConfigsByName[$type])) {
                                    // we've found an enum type argument that has a defaultValue

                                    // If defaultValue is an array, check only its first element, assuming for the sake
                                    // of performance that people won't mix e_names and e_values in defaultValue

                                    $maybeName = is_array($argConfig['defaultValue'])
                                        ? $argConfig['defaultValue'][0]
                                        : $argConfig['defaultValue'];

                                    // Check whether defaultValue is defined by enum element names.
                                    if (isset($enumConfigsByName[$type]['config']['values'][$maybeName])) {

                                        // Convert enum element names to enum element values.
                                        if (is_array($argConfig['defaultValue'])) {
                                            foreach ($argConfig['defaultValue'] as &$elem) {
                                                // perform a very unlikely sanity check
                                                if (isset($enumConfigsByName[$type]['config']['values'][$elem])) {
                                                    // swapsies!
                                                    $elem = $enumConfigsByName[$type]['config']['values'][$elem]['value'];
                                                } else {
                                                    // someone DID mix up e_names and e_values, le sigh
                                                    // todo: choose a better exception type and add a descriptive message
                                                    throw new \Exception('Your mother was a hamster and your father smelt of elderberries');
                                                }
                                            }
                                        } else {
                                            // easy peasy, swapsies!
                                            $argConfig['defaultValue'] = $enumConfigsByName[$type]['config']['values'][$maybeName]['value'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $configs;
    }
}
