<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator;

use Composer\Autoload\ClassLoader;
use Overblog\GraphQLBundle\Config\Processor;
use Overblog\GraphQLBundle\Definition\Type\CustomScalarType;
use Overblog\GraphQLBundle\Error\ResolveErrors;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use Overblog\GraphQLBundle\Validator\InputValidator;
use Overblog\GraphQLGenerator\Exception\GeneratorException;
use Overblog\GraphQLGenerator\Generator\TypeGenerator as BaseTypeGenerator;
use ReflectionException;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Filesystem\Filesystem;

class TypeGenerator extends BaseTypeGenerator
{
    public const USE_FOR_CLOSURES = '$globalVariable';
    public const DEFAULT_CONFIG_PROCESSOR = [Processor::class, 'process'];

    private const CONSTRAINTS_NAMESPACE = 'Symfony\Component\Validator\Constraints';

    private static $classMapLoaded = false;
    private $cacheDir;
    private $configProcessor;
    private $configs;
    private $useClassMap;
    private $baseCacheDir;

    public function __construct(
        string $classNamespace,
        array $skeletonDirs,
        ?string $cacheDir,
        array $configs,
        bool $useClassMap = true,
        callable $configProcessor = null,
        ?string $baseCacheDir = null,
        ?int $cacheDirMask = null
    ) {
        $this->setCacheDir($cacheDir);
        $this->configProcessor = null === $configProcessor ? static::DEFAULT_CONFIG_PROCESSOR : $configProcessor;
        $this->configs = $configs;
        $this->useClassMap = $useClassMap;
        $this->baseCacheDir = $baseCacheDir;
        if (null === $cacheDirMask) {
            // we apply permission 0777 for default cache dir otherwise we apply 0775.
            $cacheDirMask = null === $cacheDir ? 0777 : 0775;
        }

        parent::__construct($classNamespace, $skeletonDirs, $cacheDirMask);
    }

    /**
     * @return string|null
     */
    public function getBaseCacheDir(): ?string
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

    public function getCacheDir(bool $useDefault = true): ?string
    {
        if ($useDefault) {
            return $this->cacheDir ?: $this->baseCacheDir.'/overblog/graphql-bundle/__definitions__';
        } else {
            return $this->cacheDir;
        }
    }

    public function setCacheDir(?string $cacheDir): self
    {
        $this->cacheDir = $cacheDir;

        return $this;
    }

    protected function generateClassName(array $config): string
    {
        return $config['class_name'];
    }

    protected function generateClassDocBlock(array $value): string
    {
        return <<<'EOF'

/**
 * THIS FILE WAS GENERATED AND SHOULD NOT BE MODIFIED!
 */
EOF;
    }

    protected function generateClosureUseStatements(array $config): string
    {
        return \sprintf('use (%s) ', static::USE_FOR_CLOSURES);
    }

    protected function resolveTypeCode(string $alias): string
    {
        return  \sprintf('$globalVariable->get(\'typeResolver\')->resolve(%s)', \var_export($alias, true));
    }

    protected function generatePublic(array $value): string
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

    protected function generateAccess(array $value): string
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
    protected function generateComplexity(array $value): string
    {
        $resolveComplexity = parent::generateComplexity($value);
        $resolveComplexity = $this->prefixCodeWithSpaces($resolveComplexity, 1, true);

        if ('null' === $resolveComplexity) {
            return $resolveComplexity;
        }

        $code = <<<'CODE'
function ($childrenComplexity, $args = []) <closureUseStatements>{
<spaces><spaces>$resolveComplexity = %s;

<spaces><spaces>return call_user_func_array($resolveComplexity, [$childrenComplexity, $globalVariable->get('argumentFactory')->create($args)]);
<spaces>}
CODE;

        $code = \sprintf($code, $resolveComplexity);

        return $code;
    }

    /**
     * @param array $value
     *
     * @return string
     */
    protected function generateScalarType(array $value): string
    {
        return $this->callableCallbackFromArrayValue($value, 'scalarType');
    }

    protected function generateParentClassName(array $config): string
    {
        if ('custom-scalar' === $config['type']) {
            return $this->shortenClassName(CustomScalarType::class);
        } else {
            return parent::generateParentClassName($config);
        }
    }

    protected function generateTypeName(array $config): string
    {
        return $this->varExport($config['config']['name']);
    }

    protected function generateUseStrictAccess(array $value): string
    {
        $expressionLanguage = $this->getExpressionLanguage();
        $useStrictAccess = 'true';
        if (null !== $expressionLanguage && $this->arrayKeyExistsAndIsNotNull($value, 'access') && $value['access'] instanceof Expression) {
            $names = ExpressionLanguage::KNOWN_NAMES;
            if ($expressionLanguage instanceof ExpressionLanguage) {
                $names = \array_merge($names, $expressionLanguage->getGlobalNames());
            }
            $parsedExpression = $expressionLanguage->parse($value['access'], $names);
            $serializedNode = \str_replace("\n", '//', (string) $parsedExpression->getNodes());
            $useStrictAccess = false === \strpos($serializedNode, 'NameNode(name: \'object\')') ? 'true' : 'false';
        }

        return $useStrictAccess;
    }

    public function compile(int $mode): array
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

    public function loadClasses(bool $forceReload = false): void
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

    private function getClassesMap(): string
    {
        return $this->getCacheDir().'/__classes.map';
    }

    protected function generateInputFields(array $config): string
    {
        foreach ($config['fields'] as &$field) {
            if (empty($field['validation']['cascade'])) {
                continue;
            }

            $field['validation']['cascade']['isCollection'] = $this->isCollectionType($field['type']);
            $field['validation']['cascade']['referenceType'] = \trim($field['type'], '[]!');
        }

        return parent::generateInputFields($config);
    }

    /**
     * Generates additional custom code in the resolver callback.
     *
     * @param array       $value
     * @param string      $key
     * @param string|null $argDefinitions
     * @param string      $default
     * @param array|null  $compilerNames
     *
     * @return string
     *
     * @throws GeneratorException
     */
    protected function generateExtraCode(array $value, string $key, ?string $argDefinitions = null, string $default = 'null', array &$compilerNames = null): string
    {
        $resolve = $value['resolve'] ?? false;
        $groups = $value['validationGroups'] ?? null;
        $extraCode = '';

        // Generate validation code for the resolver callback
        if ('resolve' === $key) {
            $autoValidation = true;
            $autoThrow = true;

            $mapping = $this->buildValidationMapping($value);

            // If `validator` injected
            if (false !== \strpos($resolve->__toString(), 'validator')) {
                $compilerNames[] = 'validator';
                $autoValidation = false;
            }

            // If `errors` injected
            if (false !== \strpos($resolve->__toString(), 'errors')) {
                $compilerNames[] = 'errors';
                $autoThrow = false;
                $this->addUseStatement(ResolveErrors::class);

                $extraCode .= '$errors = new ResolveErrors();'."\n\n<spaces><spaces>";
            }

            if ($mapping) {
                $extraCode .= $this->generateValidation($mapping, $autoValidation, $autoThrow, $groups);
                $this->addInternalUseStatement(InputValidator::class);
            } elseif (false === $autoValidation) {
                throw new GeneratorException(
                    'Unable to inject an instance of the InputValidator. No validation constraints provided. '.
                    'Please remove the InputValidator argument from the list of dependencies of your '.
                    'resolver or provide validation configs.'
                );
            }
        }

        return $extraCode;
    }

    /**
     * Generates validation code in the resolver callback.
     *
     * @param array      $rules
     * @param bool       $autoValidation
     * @param bool       $autoThrow
     * @param array|null $groups
     *
     * @return string
     */
    protected function generateValidation(array $rules, bool $autoValidation, bool $autoThrow, ?array $groups): string
    {
        $code = $this->processTemplatePlaceHoldersReplacements('ValidatorCode', $rules, self::DEFERRED_PLACEHOLDERS);
        $code = $this->prefixCodeWithSpaces($code, 2);

        if ($autoValidation) {
            $code .= "\n\n<spaces><spaces>";

            if (null !== $groups) {
                $groups = \json_encode($groups);
            } else {
                $groups = 'null';
            }

            if ($autoThrow) {
                $code .= "\$validator->validate($groups);";
            } else {
                $code .= "\$errors->setValidationErrors(\$validator->validate($groups, false));";
            }
        }

        return $code."\n\n<spaces><spaces>";
    }

    protected function generateClassValidation(array $config): string
    {
        $config = $config['class'] ?? $config['validation'] ?? null;

        if (!$config) {
            return 'null';
        }

        $code = $this->processTemplatePlaceHoldersReplacements('ValidationConfig', $config, self::DEFERRED_PLACEHOLDERS);
        $code = $this->prefixCodeWithSpaces($code, 2);

        return $code;
    }

    protected function generateValidationMapping(array $config): string
    {
        $code = $this->processFromArray($config['properties'], 'MappingEntry');
        $code = $this->prefixCodeWithSpaces($code, 1);

        return $code;
    }

    protected function generateValidationConfig($config): string
    {
        // If no validation configuration provided.
        if (\count($config) < 2 && 'name' === \key($config)) {
            return 'null';
        }

        return $this->processTemplatePlaceHoldersReplacements('ValidationConfig', $config, self::DEFERRED_PLACEHOLDERS);
    }

    protected function generateLink(array $validationRules): ?string
    {
        $link = $validationRules['link'] ?? $validationRules['validation']['link'] ?? null;

        if (null === $link) {
            return 'null';
        }

        if (false === \strpos($link, '::')) {
            return \sprintf("'%s'", $link);
        }

        return \sprintf("['%s', '%s', '%s']", ...$this->normalizeLink((string) $link));
    }

    protected function generateConstraints(array $values): ?string
    {
        $constraints = $values['constraints'] ?? $values['validation']['constraints'] ?? null;

        if (!\is_array($constraints)) {
            return 'null';
        }

        $this->addUseStatement(self::CONSTRAINTS_NAMESPACE.' as Assert');

        $code = '';
        foreach ($constraints as $key => $constraint) {
            $code .= "\n".$this->processTemplatePlaceHoldersReplacements('RulesConfig', [\key($constraint), \current($constraint)]);
        }

        return '['.$this->prefixCodeWithSpaces($code, 2)."\n<spaces>]";
    }

    /**
     *  Converts an array into php fragment and adds proper use statements, e.g.:.
     *
     *  Input:
     *  ```
     *  ['Length', ['min' => 15, 'max' => 25]]
     *  ```
     *  Output:
     *  ```
     * "[
     *      new Assert\Length([
     *          'min' => 15,
     *          'max' => 25',
     *      ]),
     *  ]"
     * ```.
     *
     * @param array $config
     * @param int   $offset
     *
     * @return string|null
     *
     * @throws GeneratorException
     * @throws ReflectionException
     */
    protected function generateRule(array $config, $offset = 0): string
    {
        [$name, $params] = $config;

        // Custom constraint
        if (false !== \strpos($name, '\\')) {
            $prefix = '';
            $fqcn = \ltrim($name, '\\');
            $array = \explode('\\', $name);
            $name = \end($array);
            $this->addUseStatement($fqcn);
        }
        // Built-in constraint
        else {
            $prefix = 'Assert\\';
            $fqcn = self::CONSTRAINTS_NAMESPACE."\\$name";
        }

        if (!\class_exists($fqcn)) {
            throw new GeneratorException("Constraint class '$fqcn' doesn't exist.");
        }

        return "new {$prefix}{$name}({$this->stringifyValue($params, $offset)})";
    }

    /**
     * Generates the 'cascade' section of a type definition class.
     * Example:
     * ```
     *  "[
     *      'groups' => ['group1', 'group2'],
     *      'referenceType' => 'Author',
     *      'isCollection' => true
     *  ]"
     * ```.
     *
     * @param $config
     *
     * @return string
     *
     * @throws GeneratorException
     * @throws ReflectionException
     */
    protected function generateCascade($config)
    {
        $config = $config['validation'] ?? $config;

        if (isset($config['cascade'])) {
            $type = \trim($config['cascade']['referenceType'], '[]!');

            if (\in_array($type, ['ID', 'Int', 'String', 'Boolean', 'Float'])) {
                throw new GeneratorException('Cascade validation cannot be applied to built-in types.');
            }
        }

        return $this->stringifyValue($config['cascade'] ?? null, 1);
    }

    /**
     * Converts variables of different types into string:.
     *
     * Scalar values examples:
     *
     * ```
     * | Type     | Input          | String output       |
     * | ---------|--------------- | ------------------- |
     * | bool     | true           | "true"              |
     * | integer  | 100            | "100"               |
     * | float    | 14.561         | "14.561"            |
     * | string   | "jeanne d'arc" | "'jeanne d\'arc'"   |
     * | string   | "@null"        | ""                  |
     * | NULL     | null           | "null"              |
     * ```
     *
     *  Arrays are delegated to **$this->stringifyArray()**
     *
     * @param mixed $value
     * @param int   $offset - array offset
     *
     * @return string|null
     *
     * @throws ReflectionException
     */
    protected function stringifyValue($value, $offset = 0): string
    {
        switch (\gettype($value)) {
            case 'boolean':
                return $value ? 'true' : 'false';
            case 'integer':
            case 'double':
                return (string) $value;
            case 'string':
                if ('@null' === $value) {
                    return '';
                }

                return \sprintf("'%s'", $this->escapeSingleQuotes($value));
            case 'NULL':
                return 'null';
            default:
                return $this->stringifyArray($value, ++$offset);
        }
    }

    protected function escapeSingleQuotes(string $string)
    {
        return \str_replace("'", "\'", $string);
    }

    /**
     * Checks if the given array should be considered as an
     * instantiation of a new assert or a normal array, e.g:.
     *
     * - A normal array: `['min' => 15, 'max' => 25]`
     * - Instantiation of a new asser: `[[Length => ['min' => 15, 'max' => 25]]]`
     *
     * @param array $array
     * @param int   $offset
     *
     * @return string
     *
     * @throws GeneratorException
     * @throws ReflectionException
     */
    protected function stringifyArray(array $array, $offset = 1): string
    {
        $key = \key($array);
        if (\is_string($key) && 1 === \count($array) && \ctype_upper($key[0])) {
            return $this->generateRule([\key($array), \current($array)], --$offset);
        } else {
            return $this->stringifyNormalArray($array, $offset);
        }
    }

    /**
     * Recursively converts an array into a php code.
     * It can have one of 2 formats:.
     *
     *  1) With keys (example):
     * ```
     *    [
     *       'min' => 15,
     *       'max' => 25
     *    ]
     * ```
     *  2) Without keys (example):
     * ```
     *    [
     *       'App\Manager\User',
     *       'createUser'
     *    ]
     * ```
     *
     * @param $array
     * @param int $offset
     *
     * @return string
     *
     * @throws ReflectionException
     */
    protected function stringifyNormalArray($array, $offset = 1): string
    {
        $spaces = \implode('', \array_fill(0, $offset, '<spaces>'));
        $code = '';

        foreach ($array as $key => $value) {
            $code .= "\n"
                .$spaces
                .($this->isNumericArray($array) ? '' : "'$key' => ")
                .$this->stringifyValue($value, $offset)
            ;

            if ($value !== \end($array)) {
                $code .= ', ';
            }
        }

        $spaces = \substr_replace($spaces, '', 0, 8);

        return "[$code\n$spaces]";
    }

    /**
     * Checks whether an array contains only integer keys.
     *
     * @param array $array
     *
     * @return bool
     */
    protected static function isNumericArray(array $array): bool
    {
        foreach ($array as $k => $a) {
            if (!\is_int($k)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Creates and array from a formatted string, e.g.:
     * ```
     *  - "App\Entity\User::$firstName"  -> ['App\Entity\User', 'firstName', 'property']
     *  - "App\Entity\User::firstName()" -> ['App\Entity\User', 'firstName', 'getter']
     *  - "App\Entity\User::firstName"   -> ['App\Entity\User', 'firstName', 'member']
     * ```.
     *
     * @param string $link
     *
     * @return array
     */
    protected function normalizeLink(string $link): array
    {
        [$fqcn, $classMember] = \explode('::', $link);

        if ('$' === $classMember[0]) {
            return [$fqcn, \ltrim($classMember, '$'), 'property'];
        } elseif (')' === \substr($classMember, -1)) {
            return [$fqcn, \rtrim($classMember, '()'), 'getter'];
        } else {
            return [$fqcn, $classMember, 'member'];
        }
    }

    /**
     * Flattens validation configs and adds extra keys into every entry if required.
     *
     * @param array $value
     *
     * @return array|null
     */
    protected function buildValidationMapping(array $value): ?array
    {
        $properties = [];

        foreach ($value['args'] ?? [] as $name => $arg) {
            if (empty($arg['validation'])) {
                $properties[$name] = null;
                continue;
            }

            $properties[$name] = $arg['validation'];

            if (empty($arg['validation']['cascade'])) {
                continue;
            }

            $properties[$name]['cascade']['isCollection'] = $this->isCollectionType($arg['type']);
            $properties[$name]['cascade']['referenceType'] = \trim($arg['type'], '[]!');
        }

        // Merge class and field constraints
        $classValidation = $this->configs[$this->currentlyGeneratedClass]['config']['validation'] ?? [];

        if (isset($value['validation'])) {
            $classValidation = \array_replace_recursive($classValidation, $value['validation']);
        }

        $mapping = [
            'class' => empty($classValidation) ? null : $classValidation,
            'properties' => $properties,
        ];

        if (empty($classValidation) && !\array_filter($properties)) {
            return null;
        } else {
            return $mapping;
        }
    }

    /**
     * Checks if a given type is a collection, e.g.:.
     *
     *  - "[TypeName]"   - is a collection
     *  - "[TypeName!]!" - is a collection
     *  - "TypeName"     - is not a collection
     *
     * @param string $type
     *
     * @return bool
     */
    protected function isCollectionType(string $type): bool
    {
        return 2 === \count(\array_intersect(['[', ']'], \str_split($type)));
    }
}
