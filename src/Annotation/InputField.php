<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Annotation for GraphQL input field.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"PROPERTY", "METHOD"})
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class InputField extends Field
{
    /**
     * Optionnal default value.
     */
    public mixed $defaultValue;

    /**
     * @param string|null $name         The GraphQL name of the field
     * @param string|null $type         The GraphQL type of the field
     * @param string|null $complexity   A complexity expression
     * @param mixed|null  $defaultValue The default value of the field
     */
    public function __construct(
        ?string $name = null,
        ?string $type = null,
        ?string $complexity = null,
        mixed $defaultValue = null
    ) {
        parent::__construct($name, $type, null, $complexity);
        $this->defaultValue = $defaultValue;
    }
}
