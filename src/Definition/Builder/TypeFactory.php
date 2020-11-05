<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\Builder;

use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\ConfigProcessor;
use Overblog\GraphQLBundle\Definition\GraphQLServices;

final class TypeFactory
{
    private ConfigProcessor $configProcessor;
    private GraphQLServices $graphQLServices;

    public function __construct(ConfigProcessor $configProcessor, GraphQLServices $graphQLServices)
    {
        $this->configProcessor = $configProcessor;
        $this->graphQLServices = $graphQLServices;
    }

    public function create(string $class): Type
    {
        return new $class($this->configProcessor, $this->graphQLServices);
    }
}
