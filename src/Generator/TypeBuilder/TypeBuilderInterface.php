<?php

namespace Overblog\GraphQLBundle\Generator\TypeBuilder;

use Murtukov\PHPCodeGenerator\GeneratorInterface;

interface TypeBuilderInterface
{
    public function build(array $config): GeneratorInterface;
}
