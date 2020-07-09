<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Type;

use GraphQL\Error\Error;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use function sprintf;
use function str_replace;

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
        return (int) str_replace(' AC', '', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function parseLiteral($valueNode, array $variables = null)
    {
        if (!$valueNode instanceof StringValueNode) {
            throw new Error('Query error: Can only parse strings got: '.$valueNode->kind, $valueNode);
        }

        return (int) str_replace(' AC', '', $valueNode->value);
    }
}
