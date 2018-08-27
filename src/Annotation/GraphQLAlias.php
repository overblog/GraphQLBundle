<?php

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL type.
 * Use it if you don't use Doctrine ORM annotation.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class GraphQLAlias
{
    /**
     * Type.
     *
     * @var string
     */
    public $name;
}
