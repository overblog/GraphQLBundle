<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser;

use Exception;
use GraphQL\Language\AST\DefinitionNode;
use GraphQL\Language\AST\EnumTypeDefinitionNode;
use GraphQL\Language\AST\InputObjectTypeDefinitionNode;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\Parser;
use SplFileInfo;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use function array_keys;
use function array_pop;
use function call_user_func;
use function explode;
use function file_get_contents;
use function get_class;
use function in_array;
use function preg_replace;
use function sprintf;
use function trim;
use function ucfirst;

class GraphQLParser implements ParserInterface
{
    private const DEFINITION_TYPE_MAPPING = [
        NodeKind::OBJECT_TYPE_DEFINITION => 'object',
        NodeKind::INTERFACE_TYPE_DEFINITION => 'interface',
        NodeKind::ENUM_TYPE_DEFINITION => 'enum',
        NodeKind::UNION_TYPE_DEFINITION => 'union',
        NodeKind::INPUT_OBJECT_TYPE_DEFINITION => 'inputObject',
        NodeKind::SCALAR_TYPE_DEFINITION => 'customScalar',
    ];

    public static function parse(SplFileInfo $file, ContainerBuilder $container, array $configs = []): array
    {
        $container->addResource(new FileResource($file->getRealPath()));
        $content = trim(file_get_contents($file->getPathname()));
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
             * @var ObjectTypeDefinitionNode|InputObjectTypeDefinitionNode|EnumTypeDefinitionNode $typeDef
             */
            if (isset($typeDef->kind) && in_array($typeDef->kind, array_keys(self::DEFINITION_TYPE_MAPPING))) {
                $class = sprintf('\\%s\\GraphQL\\ASTConverter\\%sNode', __NAMESPACE__, ucfirst(self::DEFINITION_TYPE_MAPPING[$typeDef->kind]));
                $typesConfig[$typeDef->name->value] = call_user_func([$class, 'toConfig'], $typeDef);
            } else {
                self::throwUnsupportedDefinitionNode($typeDef);
            }
        }

        return $typesConfig;
    }

    private static function throwUnsupportedDefinitionNode(DefinitionNode $typeDef): void
    {
        $path = explode('\\', get_class($typeDef));
        throw new InvalidArgumentException(
            sprintf(
                '%s definition is not supported right now.',
                preg_replace('@DefinitionNode$@', '', array_pop($path))
            )
        );
    }
}
