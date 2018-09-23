<?php

namespace Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter;

use GraphQL\Language\AST\Node;

interface NodeInterface
{
    /**
     * @param Node $node
     *
     * @return array
     */
    public static function toConfig(Node $node);
}
