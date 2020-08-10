<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator\Converters;

interface ConverterAnnotationInterface
{
    public static function getConverterClass(): string;

    public function isCollection(): bool;
}