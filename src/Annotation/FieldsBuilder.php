<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL fields builders.
 *
 * @Annotation
 * @Target({"ANNOTATION", "CLASS"})
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class FieldsBuilder extends Builder implements NamedArgumentConstructorAnnotation
{
}
