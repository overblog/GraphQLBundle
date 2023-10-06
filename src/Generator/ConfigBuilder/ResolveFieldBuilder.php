<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\ConfigBuilder;

use Murtukov\PHPCodeGenerator\PhpFile;
use Overblog\GraphQLBundle\Generator\Model\Collection;
use Overblog\GraphQLBundle\Generator\Model\TypeConfig;
use Overblog\GraphQLBundle\Generator\ResolveInstructionBuilder;

class ResolveFieldBuilder implements ConfigBuilderInterface
{
    private ResolveInstructionBuilder $resolveInstructionBuilder;

    public function __construct(ResolveInstructionBuilder $resolveInstructionBuilder)
    {
        $this->resolveInstructionBuilder = $resolveInstructionBuilder;
    }

    public function build(TypeConfig $typeConfig, Collection $builder, PhpFile $phpFile): void
    {
        if (isset($typeConfig->resolveField)) {
            $builder->addItem('resolveField', $this->resolveInstructionBuilder->build($typeConfig, $typeConfig->resolveField));
        }
    }
}
