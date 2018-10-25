<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for operations provider.
 *
 * @Annotation
 * @Target({"CLASS"})
 */
final class Provider implements Annotation
{
    /**
     * Optionnal prefix for provider fields.
     *
     * @var string
     */
    public $prefix;
}
