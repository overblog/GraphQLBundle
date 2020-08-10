<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator\Converters;

/**
 * Converts scalar value into Doctrine entity.
 *
 * @Annotation
 */
class Entity implements ConverterAnnotationInterface
{
    /**
     * FQCN of the target entity
     */
    public string $value;

    public bool $isCollection;

    public static function getConverterClass(): string
    {
        return static::class.'Converter';
    }

    public function isCollection(): bool
    {
        return $this->isCollection ?? false;
    }
}
