<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser;

use Exception;
use GraphQL\Language\AST\DefinitionNode;
use GraphQL\Language\AST\EnumTypeDefinitionNode;
use GraphQL\Language\AST\InputObjectTypeDefinitionNode;
use GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\AST\ScalarTypeDefinitionNode;
use GraphQL\Language\AST\UnionTypeDefinitionNode;
use GraphQL\Language\Parser;
use Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter\CustomScalarNode;
use Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter\EnumNode;
use Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter\InputObjectNode;
use Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter\InterfaceNode;
use Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter\NodeInterface;
use Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter\ObjectNode;
use Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter\UnionNode;
use SplFileInfo;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

use function array_pop;
use function explode;
use function file_get_contents;
use function preg_replace;
use function sprintf;
use function trim;

class GraphQLParser implements ParserInterface
{
    protected const DEFINITION_TYPE_MAPPING = [
        NodeKind::OBJECT_TYPE_DEFINITION => ObjectNode::class,
        NodeKind::INTERFACE_TYPE_DEFINITION => InterfaceNode::class,
        NodeKind::ENUM_TYPE_DEFINITION => EnumNode::class,
        NodeKind::UNION_TYPE_DEFINITION => UnionNode::class,
        NodeKind::INPUT_OBJECT_TYPE_DEFINITION => InputObjectNode::class,
        NodeKind::SCALAR_TYPE_DEFINITION => CustomScalarNode::class,
    ];

    public static function parse(SplFileInfo $file, ContainerBuilder $container, array $configs = []): array
    {
        $container->addResource(new FileResource($file->getRealPath()));
        $content = trim((string) file_get_contents($file->getPathname()));
        $typesConfig = [];

        // allow empty files
        if (empty($content)) {
            return [];
        }
        try {
            $ast = Parser::parse($content);
        } catch (Exception $e) {
            throw new InvalidArgumentException(sprintf('An error occurred while parsing the file "%s".', $file), $e->getCode(), $e);
        }

        foreach ($ast->definitions as $typeDef) {
            /**
             * @var ObjectTypeDefinitionNode|InterfaceTypeDefinitionNode|EnumTypeDefinitionNode|UnionTypeDefinitionNode|InputObjectTypeDefinitionNode|ScalarTypeDefinitionNode $typeDef
             */
            $config = static::prepareConfig($typeDef);
            $typesConfig[$typeDef->name->value] = $config;
        }

        return $typesConfig;
    }

    protected static function createUnsupportedDefinitionNodeException(DefinitionNode $typeDef): InvalidArgumentException
    {
        $path = explode('\\', \get_class($typeDef));

        return new InvalidArgumentException(
            sprintf(
                '%s definition is not supported right now.',
                preg_replace('@DefinitionNode$@', '', array_pop($path))
            )
        );
    }

    /**
     * @return class-string<NodeInterface>
     */
    protected static function getNodeClass(DefinitionNode $typeDef): string
    {
        if (isset($typeDef->kind) && \array_key_exists($typeDef->kind, static::DEFINITION_TYPE_MAPPING)) {
            return static::DEFINITION_TYPE_MAPPING[$typeDef->kind];
        }

        throw static::createUnsupportedDefinitionNodeException($typeDef);
    }

    /**
     * @return array<string,mixed>
     */
    protected static function prepareConfig(DefinitionNode $typeDef): array
    {
        $nodeClass = static::getNodeClass($typeDef);

        return \call_user_func([$nodeClass, 'toConfig'], $typeDef);
    }
}
