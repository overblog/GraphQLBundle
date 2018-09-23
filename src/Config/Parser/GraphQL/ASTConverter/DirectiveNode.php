<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter;

use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\Directive;

class DirectiveNode implements NodeInterface
{
    public static function toConfig(Node $node): array
    {
        $config = [];

        foreach ($node->directives as $directiveDef) {
            if ('deprecated' === $directiveDef->name->value) {
                $reason = $directiveDef->arguments->count() ?
                    $directiveDef->arguments[0]->value->value : Directive::DEFAULT_DEPRECATION_REASON;

                $config['deprecationReason'] = $reason;
                break;
            }
        }

        return $config;
    }
}
