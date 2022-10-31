<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter;

final class InterfaceNode extends ObjectNode
{
    protected const TYPENAME = 'interface';
}
