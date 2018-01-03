<?php

namespace Overblog\GraphQLBundle\ExpressionLanguage;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;

class ExpressionLanguage extends BaseExpressionLanguage
{
    use ContainerAwareTrait;

    public function compile($expression, $names = [])
    {
        $names[] = 'container';
        $names[] = 'request';
        $names[] = 'security.token_storage';
        $names[] = 'token';
        $names[] = 'user';

        return parent::compile($expression, $names);
    }
}
