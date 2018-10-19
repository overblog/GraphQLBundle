<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL query.
 *
 * @Annotation
 * @Target({"METHOD"})
 */
final class Query extends Operation
{
}
