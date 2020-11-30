<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use InvalidArgumentException;
use function is_string;
use function sprintf;

class QueryTaggedServiceMappingPass extends TaggedServiceMappingPass
{
    protected function getTagName(): string
    {
        return 'overblog_graphql.query';
    }

    protected function checkRequirements(string $id, array $tag): void
    {
        parent::checkRequirements($id, $tag);

        if (isset($tag['method']) && !is_string($tag['method'])) {
            throw new InvalidArgumentException(
                sprintf('Service tagged "%s" must have valid "method" argument.', $id)
            );
        }
    }

    protected function getResolverServiceID(): string
    {
        return 'overblog_graphql.query_resolver';
    }
}
