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

    private static $typeSystems = [
        'object' => 'GraphQL\\Type\\Definition\\ObjectType',
        'interface' => 'GraphQL\\Type\\Definition\\InterfaceType',
        'enum' => 'GraphQL\\Type\\Definition\\EnumType',
        'union' => 'GraphQL\\Type\\Definition\\UnionType',
        'input-object' => 'GraphQL\\Type\\Definition\\InputObjectType',
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

    protected function varExportFromArrayValue(array $values, $key, $default = 'null')
    {
        if (!isset($values[$key])) {
            return $default;
        }

        $code = $this->varExport($values[$key], $default);

        return $code;
    }

    protected function varExport($var, $default = null)
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
                return $code = $this->getExpressionLanguage()->compile($var);

            case is_object($var):
                return $default;

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

    protected function callableCallbackFromArrayValue(array $value, $key, $argDefinitions = null, $default = 'null')
    {
        if (!isset($value[$key])) {
            return $default;
        }

        $code = $this->getSkeletonContent('ResolverClosure');

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
            preg_match_all('@\$([a-z_][a-z0-9_]+)@i', $argDefinitions, $matches);

            $argNames = isset($matches[1]) ? $matches[1] : [];
            $code = sprintf(
                $code,
                $this->shortenClassFromCode($argDefinitions),
                $this->getExpressionLanguage()->compile($value[$key], $argNames)
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

    protected function types2String(array $types)
    {
        $types = array_map(__CLASS__ . '::typeAlias2String', $types);

        return '[' . implode(', ', $types) . ']';
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
     * @param array $configs
     * @param string $outputDirectory
     * @param bool $regenerateIfExists
     * @return array
     */
    public function generateClasses(array $configs, $outputDirectory, $regenerateIfExists = false)
    {
        $classesMap = [];

        foreach ($configs as $name => $config) {
            $config['config']['name'] = isset($config['config']['name']) ? $config['config']['name'] : $name;
            $classMap = $this->generateClass($config, $outputDirectory, $regenerateIfExists);

            $classesMap = array_merge($classesMap, $classMap);
        }

        return $classesMap;
    }

    public function generateClass(array $config, $outputDirectory, $regenerateIfExists = false)
    {
        static $treatLater = ['useStatement', 'spaces'];
        $this->clearInternalUseStatements();
        $code = $this->processTemplatePlaceHoldersReplacements('TypeSystem', $config, $treatLater);
        $code = $this->processPlaceHoldersReplacements($treatLater, $code, $config) . "\n";

        $className = $this->generateClassName($config);

        $path = $outputDirectory . '/' . $className . '.php';
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if ($regenerateIfExists || !file_exists($path)) {
            file_put_contents($path, $code);
            chmod($path, 0664);
        }

        return [$this->getClassNamespace().'\\'.$className => realpath($path)];
    }
}
