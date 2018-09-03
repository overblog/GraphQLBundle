<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL access control.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class GraphQLRelayMutation extends GraphQLMutation
{
}
