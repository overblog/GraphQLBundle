<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator\Converters;

/**
 * Calls the "convert" method on the target service or "__invoke"
 * if no method specified
 *
 * @Annotation
 */
class Service implements ConverterAnnotationInterface
{
    /**
     * Service ID.
     */
    public string $value;

    /**
     * Method name to call on the target service.
     */
    public string $method;

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