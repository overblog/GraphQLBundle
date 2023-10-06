<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\ConfigBuilder;

use Murtukov\PHPCodeGenerator\PhpFile;
use Overblog\GraphQLBundle\Generator\Model\Collection;
use Overblog\GraphQLBundle\Generator\Model\TypeConfig;

class DescriptionBuilder implements ConfigBuilderInterface
{
    public function build(TypeConfig $typeConfig, Collection $builder, PhpFile $phpFile): void
    {
        if (isset($typeConfig->description)) {
            $builder->addItem('description', $typeConfig->description);
        }
    }
}
