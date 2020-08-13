<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\Builder;

use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\ConfigProcessor;
use Overblog\GraphQLBundle\Definition\GlobalVariables;

final class TypeFactory
{
    private ConfigProcessor $configProcessor;
    private GlobalVariables $globalVariables;

    public function __construct(ConfigProcessor $configProcessor, GlobalVariables $globalVariables)
    {
        $this->configProcessor = $configProcessor;
        $this->globalVariables = $globalVariables;
    }

    public function create(string $class): Type
    {
        return new $class($this->configProcessor, $this->globalVariables);
    }
}
