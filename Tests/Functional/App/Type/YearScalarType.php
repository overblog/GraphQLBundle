<?php

namespace Overblog\GraphQLBundle\Tests\Functional\App\Type;

use GraphQL\Error\Error;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils;

class YearScalarType extends ScalarType
{
    /**
     * {@inheritdoc}
     */
    public function serialize($value)
    {
        return sprintf('%s AC', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function parseValue($value)
    {
        if (!is_string($value)) {
            throw new Error(sprintf('Cannot represent following value as a valid year: %s.', Utils::printSafeJson($value)));
        }

        return (int) str_replace(' AC', '', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function parseLiteral($valueNode)
    {
        if (!$valueNode instanceof StringValueNode) {
            throw new Error('Query error: Can only parse strings got: '.$valueNode->kind, [$valueNode]);
        }

        return (int) str_replace(' AC', '', $valueNode->value);
    }
}
