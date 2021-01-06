<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use Attribute;

/**
 * Annotation for GraphQL args builders.
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD"})
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class ArgsBuilder extends Builder
{
}
