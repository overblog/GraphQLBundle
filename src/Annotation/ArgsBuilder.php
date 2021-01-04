<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use \Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL args builders.
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD"})
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class ArgsBuilder implements NamedArgumentConstructorAnnotation, Annotation
{
    /**
     * Builder name.
     *
     * @Required
     * 
     * @var string
     */
    public string $value;

    /**
     * The builder config.
     *
     * @var mixed
     */
    public $config = [];

    public function __construct(string $value, array $config = [])
    {
        $this->value = $value;
        $this->config = $config;
    }
}
