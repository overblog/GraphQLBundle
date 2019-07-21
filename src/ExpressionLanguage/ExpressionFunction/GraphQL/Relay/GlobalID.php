<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Relay\Node\GlobalId as GlobalIdHelper;
use function sprintf;

final class GlobalID extends ExpressionFunction
{
    public function __construct($name = 'globalId')
    {
        parent::__construct(
            $name,
            function (string $id, string $typeName = null): string {
                $typeName = $this->isTypeNameEmpty($typeName) ? '$info->parentType->name' : $typeName;

                return sprintf('\%s::toGlobalId(%s, %s)', GlobalIdHelper::class, $typeName, $id);
            },
            function ($arguments, $id, $typeName = null) {
                $typeName = $this->isTypeNameEmpty($typeName) ? $arguments['info']->parentType->name : $typeName;

                return GlobalIdHelper::toGlobalId($typeName, $id);
            }
        );
    }

    private function isTypeNameEmpty($typeName)
    {
        return null === $typeName || '""' === $typeName || 'null' === $typeName || 'false' === $typeName;
    }
}
