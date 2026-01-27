<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser;

use Overblog\GraphQLBundle\Config\Parser\AttributeParser;
use PHPUnit\Framework\Attributes\Group;

#[Group('legacy')]
/**
 * @requires PHP 8.
 */
final class AttributeParserTest extends MetadataParserTest
{
    public function parser(string $method, ...$args)
    {
        return AttributeParser::$method(...$args);
    }

    public function formatMetadata(string $metadata): string
    {
        return sprintf('#\[%s\]', $metadata);
    }
}
