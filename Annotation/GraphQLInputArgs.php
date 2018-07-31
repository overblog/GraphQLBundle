<?php

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for graphql type
 * Use it if you don't use Doctrine ORM annotation.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class GraphQLInputArgs
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $target;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $description;
}
