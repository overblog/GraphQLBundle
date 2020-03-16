<?php

namespace Overblog\GraphQLBundle\Generator\TypeBuilder;

use Murtukov\PHPCodeGenerator\GeneratorInterface;

interface TypeBuilderInterface
{
    public static function build(array $config, string $namespace): GeneratorInterface;
}
