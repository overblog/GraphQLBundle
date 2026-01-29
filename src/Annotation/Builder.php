<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Annotation for GraphQL builders
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"PROPERTY", "METHOD"})
 */
abstract class Builder extends Annotation
{
    /**
     * Builder name.
     */
    public ?string $name;

    /**
     * The builder config.
     */
    public array $config = [];

    /**
     * @param string|null $name   The name of the builder
     * @param array       $config The builder configuration array
     */
    public function __construct(?string $name = null, array $config = [])
    {
        $this->name = $name;
        $this->config = $config;
    }
}
