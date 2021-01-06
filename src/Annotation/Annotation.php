<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use Doctrine\Common\Annotations\AnnotationException;

abstract class Annotation
{
    protected function cumulatedAttributesException(string $attribute, string $value, string $attributeValue): void
    {
        $annotationName = str_replace('Overblog\GraphQLBundle\Annotation\\', '', get_class($this));
        throw new AnnotationException(sprintf('The @%s %s is defined by both the default attribute "%s" and the %s attribute "%s". Pick one.', $annotationName, $attribute, $value, $attribute, $attributeValue));
    }
}
