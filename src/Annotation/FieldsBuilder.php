<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use \Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL fields builders.
 *
 * @Annotation
 * @Target({"ANNOTATION", "CLASS"})
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class FieldsBuilder implements NamedArgumentConstructorAnnotation, Annotation
{
    /**
     * Builder name.
     *
     * @Required
     * 
     * @var string
     */
    public string $builder;

    /**
     * The builder config.
     *
     * @var mixed
     */
    public $builderConfig = [];

    public function __construct(string $builder, array $builderConfig = [])
    {
        $this->builder = $builder;
        $this->builderConfig = $builderConfig;
    }
}
