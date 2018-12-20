<?php

namespace Overblog\GraphQLBundle\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;

class ExpressionLanguage extends BaseExpressionLanguage
{
    const KNOWN_NAMES = ['value', 'args', 'context', 'info', 'object'];

    private $globalNames = [];

    /**
     * @param $index
     * @param $name
     */
    public function addGlobalName($index, $name)
    {
        $this->globalNames[$index] = $name;
    }

    public function getGlobalNames()
    {
        return $this->globalNames;
    }

    public function compile($expression, $names = [])
    {
        return parent::compile($expression, \array_merge($names, $this->globalNames));
    }
}
