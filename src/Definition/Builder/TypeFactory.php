<?php

namespace Overblog\GraphQLBundle\Definition\Builder;

use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\ConfigProcessor;
use Overblog\GraphQLBundle\Definition\GlobalVariables;

final class TypeFactory
{
    /** @var ConfigProcessor */
    private $configProcessor;
    /** @var GlobalVariables */
    private $globalVariables;

    public function __construct(ConfigProcessor $configProcessor, GlobalVariables $globalVariables)
    {
        $this->configProcessor = $configProcessor;
        $this->globalVariables = $globalVariables;
    }

    /**
     * @param string $class
     *
     * @return Type
     */
    public function create($class)
    {
        return new $class($this->configProcessor, $this->globalVariables);
    }
}
