<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator;

use Murtukov\PHPCodeGenerator\Config;
use Murtukov\PHPCodeGenerator\ConverterInterface;
use Murtukov\PHPCodeGenerator\PhpFile;
use Overblog\GraphQLBundle\Definition\ConfigProcessor;
use Overblog\GraphQLBundle\Definition\GraphQLServices;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Type\GeneratedTypeInterface;
use Overblog\GraphQLBundle\Generator\Converter\ExpressionConverter;
use Overblog\GraphQLBundle\Generator\Exception\GeneratorException;
use Overblog\GraphQLBundle\Generator\Model\TypeConfig;

/**
 * Service that exposes a single method `build` called for each GraphQL
 * type config to build a PhpFile object.
 *
 * {@link https://github.com/murtukov/php-code-generator}
 *
 * It's responsible for building all GraphQL types (object, input-object,
 * interface, union, enum and custom-scalar).
 *
 * Every method with prefix 'build' has a render example in it's PHPDoc.
 */
class TypeBuilder
{
    /**
     * @deprecated Use {@see ValidationRulesBuilder::CONSTRAINTS_NAMESPACE }
     */
    public const CONSTRAINTS_NAMESPACE = ValidationRulesBuilder::CONSTRAINTS_NAMESPACE;
    protected const DOCBLOCK_TEXT = 'THIS FILE WAS GENERATED AND SHOULD NOT BE EDITED MANUALLY.';

    protected AwareTypeBaseClassProvider $baseClassProvider;
    protected ConfigBuilder $configBuilder;
    protected ExpressionConverter $expressionConverter;
    protected string $namespace;

    public function __construct(
        AwareTypeBaseClassProvider $baseClassProvider,
        ConfigBuilder $configBuilder,
        ExpressionConverter $expressionConverter,
        string $namespace
    ) {
        $this->baseClassProvider = $baseClassProvider;
        $this->configBuilder = $configBuilder;
        $this->expressionConverter = $expressionConverter;
        $this->namespace = $namespace;

        // Register additional converter in the php code generator
        Config::registerConverter($expressionConverter, ConverterInterface::TYPE_STRING);
    }

    /**
     * @param array{
     *     name:          string,
     *     class_name:    string,
     *     fields:        array,
     *     description?:  string,
     *     interfaces?:   array,
     *     resolveType?:  string,
     *     validation?:   array,
     *     types?:        array,
     *     values?:       array,
     *     serialize?:    callable,
     *     parseValue?:   callable,
     *     parseLiteral?: callable,
     * } $config
     *
     * @throws GeneratorException
     */
    public function build(array $config, string $type): PhpFile
    {
        $typeConfig = new TypeConfig($config, $type);
        $file = PhpFile::new()->setNamespace($this->namespace);

        $class = $file->createClass($config['class_name'])
            ->setFinal()
            ->setExtends($this->baseClassProvider->getFQCN($type))
            ->addImplements(GeneratedTypeInterface::class, AliasedInterface::class)
            ->addConst('NAME', $config['name'])
            ->setDocBlock(static::DOCBLOCK_TEXT);

        $class->emptyLine();

        $class->createConstructor()
            ->addArgument('configProcessor', ConfigProcessor::class)
            ->addArgument(TypeGenerator::GRAPHQL_SERVICES, GraphQLServices::class)
            ->append('$config = ', $this->configBuilder->build($typeConfig, $file))
            ->emptyLine()
            ->append('parent::__construct($configProcessor->process($config))');

        $class->createMethod('getAliases', 'public')
            ->setStatic()
            ->setReturnType('array')
            ->setDocBlock('{@inheritdoc}')
            ->append('return [self::NAME]');

        return $file;
    }
}
