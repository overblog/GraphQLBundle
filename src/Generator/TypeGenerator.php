<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator;

use function array_replace_recursive;
use Composer\Autoload\ClassLoader;
use Overblog\GraphQLBundle\Config\Processor;
use Overblog\GraphQLBundle\Definition\Type\CustomScalarType;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use Overblog\GraphQLBundle\Validator\InputValidator;
use Overblog\GraphQLGenerator\Generator\TypeGenerator as BaseTypeGenerator;
use ReflectionException;
use RuntimeException;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;

class TypeGenerator extends BaseTypeGenerator
{
    public const USE_FOR_CLOSURES = '$globalVariable';

    public const DEFAULT_CONFIG_PROCESSOR = [Processor::class, 'process'];

    private $cacheDir;

    private $configProcessor;

    private $configs;

    private $useClassMap;

    private $baseCacheDir;

    private static $classMapLoaded = false;

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
        $resolveComplexity = \ltrim($this->prefixCodeWithSpaces($resolveComplexity));

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

            $field['validation']['isCollection']  = $this->isCollectionType($field['type']);
            $field['validation']['referenceType'] = trim($field['type'], '[]!');
        }

        return parent::generateInputFields($config);
    }

    protected function generateExtraCode(array $value, string $key, ?string $argDefinitions = null, string $default = 'null', array &$compilerNames = null): string
    {
        $resolve = $value['resolve'] ?? false;

        if ($key === 'resolve' && $resolve && (false !== strpos($resolve->__toString(), 'validator'))) {
            $compilerNames[] = 'validator';
            $mapping = $this->buildValidationMapping($value);
            $extraCode = $this->generateValidation($mapping);
            $this->addInternalUseStatement(InputValidator::class);
        }

        return $extraCode ?? "";
    }

    protected function generateValidation(array $rules): string
    {
        $code = $this->processTemplatePlaceHoldersReplacements('ValidatorCode', $rules, self::DEFERRED_PLACEHOLDERS);
        $code = \ltrim($this->prefixCodeWithSpaces($code, 2));

        return $code . "\n\n<spaces><spaces>";
    }

    protected function generateClassValidation(array $config): string
    {
        $config = $config['class'] ?? $config['validation'] ?? null;

        if(!$config) return 'null';

        $code = $this->processTemplatePlaceHoldersReplacements('ValidationConfig', $config, self::DEFERRED_PLACEHOLDERS);
        $code = \ltrim($this->prefixCodeWithSpaces($code, 2));

        return $code;
    }

    protected function generateValidationMapping(array $config): string
    {
        $code = $this->processFromArray($config['properties'], 'MappingEntry');
        $code = \ltrim($this->prefixCodeWithSpaces($code, 1));

        return $code;
    }

    protected function generateValidationConfig($config): string
    {
        // If no validation configuration provided.
        if (count($config) < 2 && 'name' === key($config)) {
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

        if (false === strpos($link, '::')) {
            return sprintf("'%s'", $link);
        }

        return sprintf("['%s', '%s', '%s']", ...$this->normalizeLink((string) $link));
    }

    protected function generateConstraints(array $values): ?string
    {
        $constraints = $values['constraints'] ?? $values['validation']['constraints'] ?? null;

        if(!is_array($constraints)) {
            return 'null';
        }

        $this->addUseStatement(Assert::class . ' as Assert');

        $code = '';
        foreach ($constraints as $key => $constraint) {
            $code .= "\n".$this->processTemplatePlaceHoldersReplacements('RulesConfig', [key($constraint), current($constraint)]);
        }

        return '['.$this->prefixCodeWithSpaces($code, 2)."\n<spaces>]";
    }

    protected function generateRules(array $rules): ?string
    {
        return $this->processTemplatePlaceHoldersReplacements('RulesConfig', $rules);
    }

    /**
     *  Converts an array into php fragment and adds proper use statements, e.g.:
     *  ```
     *  Input: ['Length', ['min' => 15, 'max' => 25]]
     *  Output: "[
     *      new Assert\Length([
     *          'min' => 15,
     *          'max' => 25',
     *      ]),
     *  ]"
     * ```
     *
     * @param array $config
     * @param int $offset
     * @return string|null
     * @throws ReflectionException
     */
    protected function generateRule(array $config, $offset = 0): string
    {
        [$name, $params] = $config;

        // Security constraint
        if ('UserPassword' === $name) {
            $FQCN   = SecurityAssert::class . "\\$name";
            $prefix = 'SecurityAssert\\';
            $this->addUseStatement(SecurityAssert::class . ' as SecurityAssert');
        }
        // Custom constraint
        else if (false !== strpos($name, '\\')) {
            $prefix = '';
            $FQCN   = ltrim($name, '\\');
            $array  = explode('\\', $name);
            $name   = end($array);
            $this->addUseStatement($FQCN);
        }
        // Standart constraint
        else {
            $prefix = 'Assert\\';
            $FQCN   = Assert::class . "\\$name";
        }

        if(!class_exists($FQCN)) {
            throw new RuntimeException("Constraint class '$FQCN' doesn't exist.");
        }

        return "new {$prefix}{$name}({$this->stringifyValue($params, $offset)})";
    }

    /**
     * Generates the 'cascade' section of a type definition class.
     * Example:
     *  [
     *      'groups' => ['group1', 'group2'],
     *      'referenceType' => 'Author',
     *      'isCollection' => true
     *  ]
     *
     * @param $config
     * @return string
     */
    protected function generateCascade($config)
    {
        $config  = $config['validation'] ?? $config;
        $cascade = $config['cascade'] ?? null;

        if (null === $cascade) {
            return 'null';
        }

        $template = <<<EOF
[
<spaces><spaces>'groups' => [%s],
<spaces><spaces>'referenceType' => '%s',
<spaces><spaces>'isCollection' => %s
<spaces>],
EOF;

        $groups = !empty($cascade['groups']) ? sprintf("'%s'", implode("', '", $cascade['groups'])) : '';

        return $cascade ? sprintf($template, $groups, $config['referenceType'], $config['isCollection'] ? 'true' : 'false') : 'null';
    }

    /**
     * Converts variables of different types into string:
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
     * | NULL     | null           | ""                  |
     * ```
     *
     *  Arrays are delegated to **$this->stringifyArray()**
     *
     * @param $value
     * @param $offset
     * @return string|null
     * @throws ReflectionException
     */
    protected function stringifyValue($value, $offset): string
    {
        switch (gettype($value)) {
            case 'boolean':
                return $value ? 'true' : 'false';
            case 'integer':
            case 'double':
                return (string) $value;
            case 'string':
                return sprintf("'%s'", $this->escapeSingleQuotes($value));
            case 'NULL':
                return '';
            case 'array':
                return $this->stringifyArray($value, ++$offset);
            default:
                throw new RuntimeException('Unsupported data type passed to constraint parameters.');
        }
    }

    protected function escapeSingleQuotes(string $string)
    {
        return str_replace("'", "\'", $string);
    }

    /**
     * Checks if the given array should be considered as an
     * instantiation of a new assert or a normal array, e.g:
     *
     * - A normal array: `['min' => 15, 'max' => 25]`
     * - Instantiation of a new asser: `[[Length => ['min' => 15, 'max' => 25]]]`
     *
     * @param array $array
     * @param int $offset
     * @return string
     * @throws ReflectionException
     */
    protected function stringifyArray(array $array, $offset = 1): string
    {
        if (1 === count($array) && ctype_upper(key($array)[0])) {
            return $this->generateRule([key($array), current($array)], --$offset);
        } else {
            return $this->stringifyNormalArray($array, $offset);
        }
    }

    /**
     * Recursively converts an array into a php code.
     * It can have one of 2 formats:
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
     * @param $array
     * @param int $offset
     * @return string
     * @throws ReflectionException
     */
    protected function stringifyNormalArray($array, $offset = 1): string
    {
        $spaces = implode('', array_fill(0, $offset, '<spaces>'));
        $code = "";

        foreach ($array as $key => $value) {
            $code .= "\n"
                .$spaces
                .($this->isNumericArray($array) ? "" : "'$key' => ")
                .$this->stringifyValue($value, $offset)
            ;

            if ($value !== end($array)) {
                $code .=  ', ';
            }
        }

        $spaces = substr_replace($spaces, '', 0, 8);

        return "[$code\n$spaces]";
    }

    /**
     * Checks whether an array contains only integer keys
     *
     * @param array $array
     * @return bool
     */
    protected static function isNumericArray(array $array): bool
    {
        foreach ($array as $k => $a) {
            if (!is_int($k)) return false;
        }
        return true;
    }

    /**
     * Creates and array from a formatted string, e.g.:
     * ```
     *  - "App\Entity\User::$firstName"  -> ['App\Entity\User', 'firstName', 'property']
     *  - "App\Entity\User::firstName()" -> ['App\Entity\User', 'firstName', 'getter']
     *  - "App\Entity\User::firstName"   -> ['App\Entity\User', 'firstName', 'member']
     * ```
     * @param string $link
     * @return array
     */
    protected function normalizeLink(string $link): array
    {
        [$FQCN, $classMember] = explode("::", $link);

        if ($classMember[0] === "$") {
            return [$FQCN, ltrim($classMember, "$"), 'property'];
        } else if (substr($classMember, -1) === ")") {
            return [$FQCN, rtrim($classMember, "()"), 'getter'];
        } else {
            return [$FQCN, $classMember, 'member'];
        }
    }

    /**
     * Flattens validation configs and adds extra keys into every entry if required
     *
     * @param array $value
     * @return array
     */
    protected function buildValidationMapping(array $value): array
    {
        $properties = [];

        foreach ($value['args'] ?? [] as $name => $arg) {
            if(empty($arg['validation'])) {
                $properties[$name] = null;
                continue;
            }

            $properties[$name] = $arg['validation'];
            $properties[$name]['isCollection']  = $this->isCollectionType($arg['type']);
            $properties[$name]['referenceType'] = trim($arg['type'], '[]!');
        }

        // Merge class and field constraints
        $classValidation = $this->configs[$this->currentlyGeneratedClass]['config']['validation'] ?? null;

        if ($classValidation && isset($value['validation'])) {
            $classValidation = array_replace_recursive($classValidation, $value['validation']);
        }

        return [
            'class' => $classValidation,
            'properties' => $properties,
        ];
    }

    /**
     * Checks if a given type is a collection, e.g.:
     *
     *  - "[TypeName]"   - is a collection
     *  - "[TypeName!]!" - is a collection
     *  - "TypeName"     - is not a collection
     *
     * @param string $type
     * @return bool
     */
    protected function isCollectionType(string $type): bool
    {
        return count(array_intersect(['[', ']'], str_split($type))) === 2;
    }
}
