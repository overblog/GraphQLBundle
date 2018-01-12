<?php

namespace Overblog\GraphQLBundle\Definition\ConfigProcessor;

use Overblog\GraphQLBundle\Definition\LazyConfig;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;

final class VariablesInjectorConfigProcessor implements ConfigProcessorInterface
{
    private $variables = [];

    private $expressionLanguage;

    public function __construct(ExpressionLanguage $expressionLanguage)
    {
        $this->expressionLanguage = $expressionLanguage;
    }

    public function addVariable($name, $value = null)
    {
        $this->variables[$name] = $value;
        $this->expressionLanguage->addGlobalName(sprintf('vars[\'%s\']', $name), $name);
    }

    /**
     * {@inheritdoc}
     */
    public function process(LazyConfig $lazyConfig)
    {
        $vars = $lazyConfig->getVars();
        foreach ($this->variables as $name => $value) {
            $vars[$name] = $value;
        }

        return $lazyConfig;
    }
}
