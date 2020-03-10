<?php

namespace Overblog\GraphQLBundle\Generator\Builder;

use Murtukov\PHPCodeGenerator\GeneratorInterface;

abstract class AbstractBuilder
{
    public const DEFAULT_NAMESPACE = '';

    abstract public function build(array $config): GeneratorInterface;
}
