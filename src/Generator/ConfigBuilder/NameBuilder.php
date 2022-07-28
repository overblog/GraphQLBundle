<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\ConfigBuilder;

use Murtukov\PHPCodeGenerator\Literal;
use Murtukov\PHPCodeGenerator\PhpFile;
use Overblog\GraphQLBundle\Generator\Model\Collection;
use Overblog\GraphQLBundle\Generator\Model\TypeConfig;

class NameBuilder implements ConfigBuilderInterface
{
    public function build(TypeConfig $typeConfig, Collection $builder, PhpFile $phpFile): void
    {
        $builder->addItem('name', new Literal('self::NAME'));
    }
}
