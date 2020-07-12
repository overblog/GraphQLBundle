<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator\Converters;

abstract class Converter
{
    abstract function convert($values, ConverterAnnotationInterface $annotation);
}
