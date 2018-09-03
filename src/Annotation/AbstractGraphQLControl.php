<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL control.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
abstract class AbstractGraphQLControl
{
    /**
     * @var string
     */
    public $method;
}
