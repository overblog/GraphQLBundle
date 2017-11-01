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

use GraphQL\Type\Definition\Type;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

abstract class AbstractTypeGenerator extends AbstractClassGenerator
{
    const DEFAULT_CLASS_NAMESPACE = 'Overblog\\CG\\GraphQLGenerator\\__Schema__';

    const MODE_DRY_RUN = 1;
    const MODE_MAPPING_ONLY = 2;
    const MODE_WRITE = 4;
    const MODE_OVERRIDE = 8;

    protected static $deferredPlaceHolders = ['useStatement', 'spaces', 'closureUseStatements'];

    protected static $closureTemplate = <<<EOF
function (%s) <closureUseStatements>{
<spaces><spaces>return %s;
<spaces>}
EOF;

    private static $typeSystems = [
        'object' => 'GraphQL\\Type\\Definition\\ObjectType',
        'interface' => 'GraphQL\\Type\\Definition\\InterfaceType',
        'enum' => 'GraphQL\\Type\\Definition\\EnumType',
        'union' => 'GraphQL\\Type\\Definition\\UnionType',
        'input-object' => 'GraphQL\\Type\\Definition\\InputObjectType',
        'custom-scalar' => 'GraphQL\\Type\\Definition\\CustomScalarType',
    ];


    private static $internalTypes = [
        Type::STRING => '\\GraphQL\\Type\\Definition\\Type::string()',
        Type::INT => '\\GraphQL\\Type\\Definition\\Type::int()',
        Type::FLOAT => '\\GraphQL\\Type\\Definition\\Type::float()',
        Type::BOOLEAN => '\\GraphQL\\Type\\Definition\\Type::boolean()',
        Type::ID => '\\GraphQL\\Type\\Definition\\Type::id()',
    ];

    private static $wrappedTypes = [
        'NonNull' => '\\GraphQL\\Type\\Definition\\Type::nonNull',
        'ListOf' => '\\GraphQL\\Type\\Definition\\Type::listOf',
    ];

    private $canManageExpressionLanguage = false;

    /**
     * @var null|ExpressionLanguage
     */
    private $expressionLanguage;

    /**
     * @param string           $classNamespace The namespace to use for the classes.
     * @param string[]|string  $skeletonDirs
     */
    public function __construct($classNamespace = self::DEFAULT_CLASS_NAMESPACE, $skeletonDirs = [])
    {
        parent::__construct($classNamespace, $skeletonDirs);
    }

    public function setExpressionLanguage(ExpressionLanguage $expressionLanguage = null)
    {
        $this->expressionLanguage = $expressionLanguage;
        $this->canManageExpressionLanguage = null !== $expressionLanguage;

        return $this;
    }

    public function getExpressionLanguage()
    {
        return $this->expressionLanguage;
    }

    public function isExpression($str)
    {
        return $this->canManageExpressionLanguage && $str instanceof Expression;
    }

    public static function getInternalTypes($name)
    {
        return isset(self::$internalTypes[$name]) ? self::$internalTypes[$name] : null;
    }

    public static function getWrappedType($name)
    {
        return isset(self::$wrappedTypes[$name]) ? self::$wrappedTypes[$name] : null;
    }

    protected function generateParentClassName(array $config)
    {
        return $this->shortenClassName(self::$typeSystems[$config['type']]);
    }

    protected function generateClassName(array $config)
    {
        return $config['config']['name'] . 'Type';
    }

    protected function generateClassDocBlock(array $config)
    {
        $className = $this->generateClassName($config);
        $namespace = $this->getClassNamespace();

        return <<<EOF

/**
 * Class $className
 * @package $namespace
 */
EOF;
    }

    protected function varExportFromArrayValue(array $values, $key, $default = 'null', array $compilerNames = [])
    {
        if (!isset($values[$key])) {
            return $default;
        }

        $code = $this->varExport($values[$key], $default, $compilerNames);

        return $code;
    }

    protected function varExport($var, $default = null, array $compilerNames = [])
    {
        switch (true) {
            case is_array($var):
                $indexed = array_keys($var) === range(0, count($var) - 1);
                $r = [];
                foreach ($var as $key => $value) {
                    $r[] = ($indexed ? '' : $this->varExport($key, $default) . ' => ')
                        . $this->varExport($value, $default);
                }
                return "[" . implode(", ", $r)  . "]";

            case $this->isExpression($var):
                return $code = $this->getExpressionLanguage()->compile($var, $compilerNames);

            case is_object($var):
                return $default;

            case is_string($var):
                $string = var_export($var, true);

                // handle multi-line strings
                $lines = explode("\n", $string);
                if (count($lines) > 1) {
                    $firstLine = sprintf('%s\' . "\n"', array_shift($lines));
                    $lastLine = sprintf("'%s", array_pop($lines));
                    $lines = array_map(
                        function ($line) {
                            return sprintf('\'%s\' . "\n"', $line);
                        },
                        $lines
                    );
                    array_unshift($lines, $firstLine);
                    array_push($lines, $lastLine);
                    $string = implode(' . ', $lines);
                }

                return $string;

            default:
                return var_export($var, true);
        }
    }

    protected function processFromArray(array $values, $templatePrefix)
    {
        $code = '';

        foreach ($values as $name => $value) {
            $value['name'] = isset($value['name']) ? $value['name'] : $name;
            $code .= "\n" . $this->processTemplatePlaceHoldersReplacements($templatePrefix . 'Config', $value);
        }

        return '[' . $this->prefixCodeWithSpaces($code, 2) . "\n<spaces>]";
    }

    protected function callableCallbackFromArrayValue(array $value, $key, $argDefinitions = null, $default = 'null', array $compilerNames = null)
    {
        if (!$this->arrayKeyExistsAndIsNotNull($value, $key)) {
            return $default;
        }

        $code = static::$closureTemplate;

        if (is_callable($value[$key])) {
            $func = $value[$key];
            $code = sprintf($code, null, 'call_user_func_array(%s, func_get_args())');

            if (is_array($func) && isset($func[0]) && is_string($func[0])) {
                $code = sprintf($code, $this->varExport($func));

                return $code;
            } elseif (is_string($func)) {
                $code = sprintf($code, var_export($func, true));

                return $code;
            }
        } elseif ($this->isExpression($value[$key])) {
            if (null === $compilerNames) {
                preg_match_all('@\$([a-z_][a-z0-9_]+)@i', $argDefinitions, $matches);
                $compilerNames = isset($matches[1]) ? $matches[1] : [];
            }
            $code = sprintf(
                $code,
                $this->shortenClassFromCode($argDefinitions),
                $this->getExpressionLanguage()->compile($value[$key], $compilerNames)
            );

            return $code;
        } elseif (!is_object($value[$key])) {
            $code = sprintf($code, null, $this->varExportFromArrayValue($value, $key, $default));

            return $code;
        }

        return $default;
    }

    protected function generateConfig(array $config)
    {
        $template = str_replace(' ', '', ucwords(str_replace('-', ' ', $config['type']))) . 'Config';
        $code = $this->processTemplatePlaceHoldersReplacements($template, $config['config']);
        $code = ltrim($this->prefixCodeWithSpaces($code, 2));

        return $code;
    }

    protected function generateClosureUseStatements(array $config)
    {
        return null;
    }

    protected function typeAlias2String($alias)
    {
        // Non-Null
        if ('!' === $alias[strlen($alias) - 1]) {
            return sprintf('%s(%s)', $this->shortenClassName(static::getWrappedType('NonNull')), $this->typeAlias2String(substr($alias, 0, -1)));
        }
        // List
        if ('[' === $alias[0]) {
            $got = $alias[strlen($alias) - 1];
            if (']' !== $got) {
                throw new \RuntimeException(
                    sprintf('Malformed ListOf wrapper type %s expected "]" but got %s.', json_encode($alias), json_encode($got))
                );
            }

            return sprintf('%s(%s)', $this->shortenClassName(static::getWrappedType('ListOf')), $this->typeAlias2String(substr($alias, 1, -1)));
        }

        if (null !== ($systemType = static::getInternalTypes($alias))) {
            return $this->shortenClassName($systemType);
        }

        return $this->resolveTypeCode($alias);
    }

    protected function resolveTypeCode($alias)
    {
        return $alias . 'Type::getInstance()';
    }

    protected function resolveTypesCode(array $values, $key)
    {
        if (isset($values[$key])) {
            $types = sprintf(static::$closureTemplate, '', $this->types2String($values[$key]));
        } else {
            $types = '[]';
        }

        return  $types;
    }

    protected function types2String(array $types)
    {
        $types = array_map(__CLASS__ . '::typeAlias2String', $types);

        return '[' . implode(', ', $types) . ']';
    }

    protected function arrayKeyExistsAndIsNotNull(array $value, $key)
    {
        return array_key_exists($key, $value) && null !== $value[$key];
    }

    /**
     * Configs has the following structure:
     * <code>
     * [
     *     [
     *         'type' => 'object', // the file type
     *         'config' => [], // the class config
     *     ],
     *     [
     *         'type' => 'interface',
     *         'config' => [],
     *     ],
     *     //...
     * ]
     * </code>
     *
     * @param array    $configs
     * @param string   $outputDirectory
     * @param int|bool $mode
     *
     * @return array
     */
    public function generateClasses(array $configs, $outputDirectory, $mode = false)
    {
        $classesMap = [];

        foreach ($configs as $name => $config) {
            $config['config']['name'] = isset($config['config']['name']) ? $config['config']['name'] : $name;
            $classMap = $this->generateClass($config, $outputDirectory, $mode);

            $classesMap = array_merge($classesMap, $classMap);
        }

        return $classesMap;
    }

    /**
     * @param array    $config
     * @param string   $outputDirectory
     * @param int|bool $mode true consider as MODE_WRITE|MODE_OVERRIDE and false as MODE_WRITE
     *
     * @return array
     */
    public function generateClass(array $config, $outputDirectory, $mode = false)
    {
        if (true === $mode) {
            $mode = self::MODE_WRITE|self::MODE_OVERRIDE;
        } elseif (false === $mode) {
            $mode = self::MODE_WRITE;
        }

        $className = $this->generateClassName($config);
        $path = $outputDirectory . '/' . $className . '.php';

        if (!($mode & self::MODE_MAPPING_ONLY)) {
            $this->clearInternalUseStatements();
            $code = $this->processTemplatePlaceHoldersReplacements('TypeSystem', $config, static::$deferredPlaceHolders);
            $code = $this->processPlaceHoldersReplacements(static::$deferredPlaceHolders, $code, $config) . "\n";

            if ($mode & self::MODE_WRITE) {
                $dir = dirname($path);
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                if (($mode & self::MODE_OVERRIDE) || !file_exists($path)) {
                    file_put_contents($path, $code);
                    chmod($path, 0664);
                }
            }
        }

        return [$this->getClassNamespace().'\\'.$className => $path];
    }
}
