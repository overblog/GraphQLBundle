<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter;

use GraphQL\Language\AST\Node;

interface NodeInterface
{
    /**
     * @return array<string,mixed>
     */
    public static function toConfig(Node $node): array;
}
