<?php

declare(strict_types=1);

/*
 * This file is part of the OverblogGraphQLPhpGenerator package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLGenerator\Generator;

use GraphQL\Type\Definition\CustomScalarType;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

abstract class AbstractTypeGenerator extends AbstractClassGenerator
{
    public const DEFAULT_CLASS_NAMESPACE = 'Overblog\\CG\\GraphQLGenerator\\__Schema__';

    protected const DEFERRED_PLACEHOLDERS = ['useStatement', 'spaces', 'closureUseStatements'];

    protected const CLOSURE_TEMPLATE = <<<EOF
function (%s) <closureUseStatements>{
<spaces><spaces>%sreturn %s;
<spaces>}
EOF;

    private const TYPE_SYSTEMS = [
        'object' => ObjectType::class,
        'interface' => InterfaceType::class,
        'enum' => EnumType::class,
        'union' => UnionType::class,
        'input-object' => InputObjectType::class,
        'custom-scalar' => CustomScalarType::class,
    ];

    private const INTERNAL_TYPES = [
        Type::STRING => '\\GraphQL\\Type\\Definition\\Type::string()',
        Type::INT => '\\GraphQL\\Type\\Definition\\Type::int()',
        Type::FLOAT => '\\GraphQL\\Type\\Definition\\Type::float()',
        Type::BOOLEAN => '\\GraphQL\\Type\\Definition\\Type::boolean()',
        Type::ID => '\\GraphQL\\Type\\Definition\\Type::id()',
    ];

    private const WRAPPED_TYPES = [
        'NonNull' => '\\GraphQL\\Type\\Definition\\Type::nonNull',
        'ListOf' => '\\GraphQL\\Type\\Definition\\Type::listOf',
    ];

    private $canManageExpressionLanguage = false;

    /**
     * @var null|ExpressionLanguage
     */
    private $expressionLanguage;

    /**
     * @var int
     */
    protected $cacheDirMask;

    /**
     * @var string
     */
    protected $currentlyGeneratedClass;

    /**
     * @param string $classNamespace The namespace to use for the classes.
     * @param string[]|string $skeletonDirs
     * @param int $cacheDirMask
     */
    public function __construct(string $classNamespace = self::DEFAULT_CLASS_NAMESPACE, $skeletonDirs = [], int $cacheDirMask = 0775)
    {
        parent::__construct($classNamespace, $skeletonDirs);
        $this->cacheDirMask = $cacheDirMask;
    }

    public function setExpressionLanguage(ExpressionLanguage $expressionLanguage = null): self
    {
        $this->expressionLanguage = $expressionLanguage;
        $this->canManageExpressionLanguage = null !== $expressionLanguage;

        return $this;
    }

    public function getExpressionLanguage(): ExpressionLanguage
    {
        return $this->expressionLanguage;
    }

    public function isExpression($str): bool
    {
        return $this->canManageExpressionLanguage && $str instanceof Expression;
    }

    public static function getInternalTypes(string $name): ?string
    {
        return isset(self::INTERNAL_TYPES[$name]) ? self::INTERNAL_TYPES[$name] : null;
    }

    public static function getWrappedType(string $name): ?string
    {
        return isset(self::WRAPPED_TYPES[$name]) ? self::WRAPPED_TYPES[$name] : null;
    }

    protected function generateParentClassName(array $config): string
    {
        return $this->shortenClassName(self::TYPE_SYSTEMS[$config['type']]);
    }

    protected function generateClassName(array $config): string
    {
        return $config['config']['name'].'Type';
    }

    protected function generateClassDocBlock(array $config): string
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

    protected function varExportFromArrayValue(array $values, string $key, string $default = 'null', array $compilerNames = []): string
    {
        if (!isset($values[$key])) {
            return $default;
        }

        $code = $this->varExport($values[$key], $default, $compilerNames);

        return $code;
    }

    protected function varExport($var, ?string $default = null, array $compilerNames = []): ?string
    {
        switch (true) {
            case \is_array($var):
                $indexed = \array_keys($var) === \range(0, \count($var) - 1);
                $r = [];
                foreach ($var as $key => $value) {
                    $r[] = ($indexed ? '' : $this->varExport($key, $default).' => ')
                        .$this->varExport($value, $default);
                }
                return "[".\implode(", ", $r)."]";

            case $this->isExpression($var):
                return $code = $this->getExpressionLanguage()->compile($var, $compilerNames);

            case \is_object($var):
                return $default;

            case \is_string($var):
                $string = \var_export($var, true);

                // handle multi-line strings
                $lines = \explode("\n", $string);
                if (\count($lines) > 1) {
                    $firstLine = \sprintf('%s\' . "\n"', \array_shift($lines));
                    $lastLine = \sprintf("'%s", \array_pop($lines));
                    $lines = \array_map(
                        function ($line) {
                            return \sprintf('\'%s\' . "\n"', $line);
                        },
                        $lines
                    );
                    \array_unshift($lines, $firstLine);
                    \array_push($lines, $lastLine);
                    $string = \implode(' . ', $lines);
                }

                return $string;

            default:
                return \var_export($var, true);
        }
    }

    protected function processFromArray(array $values, string $templatePrefix)
    {
        $code = '';

        foreach ($values as $name => $value) {
            $value['name'] = $value['name'] ?? $name;
            $code .= "\n".$this->processTemplatePlaceHoldersReplacements($templatePrefix.'Config', $value);
        }

        return '['.$this->prefixCodeWithSpaces($code, 2)."\n<spaces>]";
    }

    protected function callableCallbackFromArrayValue(array $value, string $key, ?string $argDefinitions = null, string $default = 'null', array $compilerNames = null)
    {
        if (!$this->arrayKeyExistsAndIsNotNull($value, $key)) {
            return $default;
        }

        $code = static::CLOSURE_TEMPLATE;

        if (\is_callable($value[$key])) {
            $func = $value[$key];
            $code = \sprintf($code, null, null, '\call_user_func_array(%s, \func_get_args())');

            if (\is_array($func) && isset($func[0]) && \is_string($func[0])) {
                $code = \sprintf($code, $this->varExport($func));

                return $code;
            } elseif (\is_string($func)) {
                $code = \sprintf($code, \var_export($func, true));

                return $code;
            }
        } elseif ($this->isExpression($value[$key])) {
            if (null === $compilerNames) {
                $compilerNames = [];
                if (null !== $argDefinitions) {
                    \preg_match_all('@\$([a-z_][a-z0-9_]+)@i', $argDefinitions, $matches);
                    $compilerNames = $matches[1] ?? [];
                }
            }

            $extraCode = $this->generateExtraCode($value, $key, $argDefinitions, $default, $compilerNames);

            $code = \sprintf(
                $code,
                $this->shortenClassFromCode($argDefinitions),
                $extraCode,
                $this->getExpressionLanguage()->compile($value[$key], $compilerNames)
            );

            return $code;
        } elseif (!\is_object($value[$key])) {
            $code = \sprintf($code, null, null, $this->varExportFromArrayValue($value, $key, $default));

            return $code;
        }

        return $default;
    }

    protected function generateConfig(array $config)
    {
        $template = \str_replace(' ', '', \ucwords(\str_replace('-', ' ', $config['type']))).'Config';
        $code = $this->processTemplatePlaceHoldersReplacements($template, $config['config']);
        $code = \ltrim($this->prefixCodeWithSpaces($code, 2));

        return $code;
    }

    protected function generateClosureUseStatements(array $config): ?string
    {
        return null;
    }

    protected function typeAlias2String($alias): string
    {
        // Non-Null
        if ('!' === $alias[\strlen($alias) - 1]) {
            return \sprintf('%s(%s)', $this->shortenClassName(static::getWrappedType('NonNull')), $this->typeAlias2String(\substr($alias, 0, -1)));
        }
        // List
        if ('[' === $alias[0]) {
            $got = $alias[\strlen($alias) - 1];
            if (']' !== $got) {
                throw new \RuntimeException(
                    \sprintf('Malformed ListOf wrapper type %s expected "]" but got %s.', \json_encode($alias), \json_encode($got))
                );
            }

            return \sprintf('%s(%s)', $this->shortenClassName(static::getWrappedType('ListOf')), $this->typeAlias2String(\substr($alias, 1, -1)));
        }

        if (null !== ($systemType = static::getInternalTypes($alias))) {
            return $this->shortenClassName($systemType);
        }

        return $this->resolveTypeCode($alias);
    }

    protected function resolveTypeCode(string $alias): string
    {
        return $alias.'Type::getInstance()';
    }

    protected function resolveTypesCode(array $values, string $key): string
    {
        if (isset($values[$key])) {
            $types = \sprintf(static::CLOSURE_TEMPLATE, '', '', $this->types2String($values[$key]));
        } else {
            $types = '[]';
        }

        return  $types;
    }

    protected function types2String(array $types): string
    {
        $types = \array_map(__CLASS__.'::typeAlias2String', $types);

        return '['.\implode(', ', $types).']';
    }

    protected function arrayKeyExistsAndIsNotNull(array $value, $key): bool
    {
        return \array_key_exists($key, $value) && null !== $value[$key];
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
     * @param int $mode
     *
     * @return array
     */
    public function generateClasses(array $configs, ?string $outputDirectory, int $mode = self::MODE_WRITE): array
    {
        $classesMap = [];

        foreach ($configs as $name => $config) {
            $config['config']['name'] = $config['config']['name'] ?? $name;
            $classMap = $this->generateClass($config, $outputDirectory, $mode);

            $classesMap = \array_merge($classesMap, $classMap);
        }

        return $classesMap;
    }

    /**
     * @param array    $config
     * @param string   $outputDirectory
     * @param int      $mode
     *
     * @return array
     */
    public function generateClass(array $config, ?string $outputDirectory, int $mode = self::MODE_WRITE): array
    {
        $this->currentlyGeneratedClass = $config['config']['name'];

        $className = $this->generateClassName($config);
        $path = $outputDirectory.'/'.$className.'.php';

        if (!($mode & self::MODE_MAPPING_ONLY)) {
            $this->clearInternalUseStatements();
            $code = $this->processTemplatePlaceHoldersReplacements('TypeSystem', $config, static::DEFERRED_PLACEHOLDERS);
            $code = $this->processPlaceHoldersReplacements(static::DEFERRED_PLACEHOLDERS, $code, $config)."\n";

            if ($mode & self::MODE_WRITE) {
                $dir = \dirname($path);
                if (!\is_dir($dir)) {
                    \mkdir($dir, $this->cacheDirMask, true);
                }
                if (($mode & self::MODE_OVERRIDE) || !\file_exists($path)) {
                    \file_put_contents($path, $code);
                }
            }
        }

        $this->currentlyGeneratedClass = null;

        return [$this->getClassNamespace().'\\'.$className => $path];
    }

    /**
     * Adds an extra code into resolver closure before 'return' statement
     *
     * @param array         $value
     * @param string        $key
     * @param string|null   $argDefinitions
     * @param string        $default
     * @param array|null    $compilerNames
     * @return string|null
     */
    abstract protected function generateExtraCode(array $value, string $key, ?string $argDefinitions = null, string $default = 'null', array &$compilerNames = null): ?string;
}
