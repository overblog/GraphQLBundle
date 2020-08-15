<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL access on fields.
 *
 * @Annotation
 * @Target({"CLASS", "PROPERTY", "METHOD"})
 */
final class Access implements Annotation
{
    /**
     * Field access.
     *
     * @var string
     */
    public $value;

    /**
     * Should we return null in access denied.
     *
     * @var bool
     */
    public $nullOnDenied;
}
