<?php

namespace Overblog\GraphBundle\ExpressionLanguage;


use Symfony\Component\DependencyInjection\ExpressionLanguage as BaseExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheInterface;

class ExpressionLanguage extends BaseExpressionLanguage
{
    public function __construct(ParserCacheInterface $parser = null, array $providers = [])
    {
        // prepend the default provider to let users override it easily
        array_unshift($providers, new ConfigExpressionProvider());

        parent::__construct($parser, $providers);
    }
}
