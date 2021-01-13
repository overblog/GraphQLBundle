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
    public function __construct(string $name = null, array $config = null, string $builder = null, array $builderConfig = null)
    {
        parent::__construct($name ?: $builder, $config ?: $builderConfig ?: []);
        if (null !== $builder) {
            @trigger_error('The attributes "builder" on annotation @GQL\FieldsBuilder is deprecated as of 0.14 and will be removed in 1.0. Use "name" attribute instead.', E_USER_DEPRECATED);
        }
        if (null !== $builderConfig) {
            @trigger_error('The attributes "builderConfig" on annotation @GQL\FieldsBuilder is deprecated as of 0.14 and will be removed in 1.0. Use "config" attribute instead.', E_USER_DEPRECATED);
        }
    }
}
