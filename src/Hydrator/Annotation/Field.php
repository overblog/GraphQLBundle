<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Field
{
    /**
     * @Required
     */
    public string $name;

    public function __construct($values)
    {
        $this->name = $values['value'] ?? $values['mapFrom'];
    }
}
