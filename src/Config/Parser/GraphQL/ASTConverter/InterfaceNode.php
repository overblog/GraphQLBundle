<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter;

use Overblog\GraphQLBundle\Enum\TypeEnum;

class InterfaceNode extends ObjectNode
{
    protected const TYPENAME = TypeEnum::INTERFACE;
}
