<?php

namespace Overblog\GraphQLBundle\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;

class ExpressionLanguage extends BaseExpressionLanguage
{
    private $globalNames = [];

    /**
     * @param $index
     * @param $name
     */
    public function addGlobalName($index, $name)
    {
        $this->globalNames[$index] = $name;
    }

    public function compile($expression, $names = [])
    {
        return parent::compile($expression, \array_merge($names, $this->globalNames));
    }
}
