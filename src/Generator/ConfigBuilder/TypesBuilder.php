<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\ConfigBuilder;

use Murtukov\PHPCodeGenerator\ArrowFunction;
use Murtukov\PHPCodeGenerator\PhpFile;
use Overblog\GraphQLBundle\Generator\Collection;
use Overblog\GraphQLBundle\Generator\Model\TypeConfig;
use Overblog\GraphQLBundle\Generator\TypeGenerator;

class TypesBuilder implements ConfigBuilderInterface
{
    /**
     * TODO: use single source for all usages (create a provider).
     */
    protected string $gqlServices = '$' . TypeGenerator::GRAPHQL_SERVICES;

    public function build(TypeConfig $typeConfig, Collection $builder, PhpFile $phpFile): void
    {
        if (isset($typeConfig->types) && !empty($typeConfig->types)) {
            $items = array_map(fn ($type) => "$this->gqlServices->getType('$type')", $typeConfig->types);
            $builder->addItem('types', ArrowFunction::new(Collection::numeric($items, true)));
        }
    }
}
