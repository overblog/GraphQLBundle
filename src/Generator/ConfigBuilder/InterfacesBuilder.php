<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\ConfigBuilder;

use Murtukov\PHPCodeGenerator\ArrowFunction;
use Murtukov\PHPCodeGenerator\PhpFile;
use Overblog\GraphQLBundle\Generator\Model\Collection;
use Overblog\GraphQLBundle\Generator\Model\TypeConfig;
use Overblog\GraphQLBundle\Generator\TypeGenerator;

class InterfacesBuilder implements ConfigBuilderInterface
{
    public function build(TypeConfig $typeConfig, Collection $builder, PhpFile $phpFile): void
    {
        if (isset($typeConfig->interfaces) && !empty($typeConfig->interfaces)) {
            $gqlServices = TypeGenerator::GRAPHQL_SERVICES_EXPR;
            $items = array_map(static fn ($type) => "{$gqlServices}->getType('$type')", $typeConfig->interfaces);
            $builder->addItem('interfaces', ArrowFunction::new(Collection::numeric($items, true)));
        }
    }
}
