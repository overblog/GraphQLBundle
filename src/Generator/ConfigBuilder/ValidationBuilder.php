<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\ConfigBuilder;

use Murtukov\PHPCodeGenerator\PhpFile;
use Overblog\GraphQLBundle\Generator\Model\Collection;
use Overblog\GraphQLBundle\Generator\Model\TypeConfig;
use Overblog\GraphQLBundle\Generator\ValidationRulesBuilder;

class ValidationBuilder implements ConfigBuilderInterface
{
    protected ValidationRulesBuilder $validationRulesBuilder;

    public function __construct(ValidationRulesBuilder $validationRulesBuilder)
    {
        $this->validationRulesBuilder = $validationRulesBuilder;
    }

    public function build(TypeConfig $typeConfig, Collection $builder, PhpFile $phpFile): void
    {
        // only by input-object types (for class level validation)
        if (isset($typeConfig->validation)) {
            $builder->addItem('validation', $this->validationRulesBuilder->build($typeConfig->validation, $phpFile));
        }
    }
}
