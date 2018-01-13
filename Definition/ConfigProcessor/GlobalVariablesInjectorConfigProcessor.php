<?php

namespace Overblog\GraphQLBundle\Definition\ConfigProcessor;

use Overblog\GraphQLBundle\Definition\LazyConfig;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;

final class GlobalVariablesInjectorConfigProcessor implements ConfigProcessorInterface
{
    private $globalVariables = [];

    private $expressionLanguage;

    public function __construct(ExpressionLanguage $expressionLanguage)
    {
        $this->expressionLanguage = $expressionLanguage;
    }

    public function addGlobalVariable($name, $globalVariable, $isPublic = true)
    {
        $this->globalVariables[$name] = $globalVariable;
        if ($isPublic) {
            $this->expressionLanguage->addGlobalName(sprintf('globalVariables->get(\'%s\')', $name), $name);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(LazyConfig $lazyConfig)
    {
        $globalVariables = $lazyConfig->getGlobalVariables();
        foreach ($this->globalVariables as $name => $variable) {
            $globalVariables[$name] = $variable;
        }

        return $lazyConfig;
    }
}
