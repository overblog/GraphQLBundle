<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter;

final class InputObjectNode extends ObjectNode
{
    protected const TYPENAME = 'input-object';
}
