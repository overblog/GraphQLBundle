<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use InvalidArgumentException;
use function is_string;
use function sprintf;
use function trigger_error;
use const E_USER_DEPRECATED;

@trigger_error(sprintf('The "%s" class is deprecated since 0.14 and will be removed in 1.0. Use "%s" instead.', ResolverTaggedServiceMappingPass::class, QueryTaggedServiceMappingPass::class), E_USER_DEPRECATED);

/**
 * TODO: remove this class in 1.0
 *
 * @deprecated since 0.14 and will be removed in 1.0. Use Overblog\GraphQLBundle\DependencyInjection\Compiler\QueryTaggedServiceMappingPass instead.
 * @codeCoverageIgnore
 */
class ResolverTaggedServiceMappingPass extends TaggedServiceMappingPass
{
    protected function getTagName(): string
    {
        return 'overblog_graphql.resolver';
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
