<?php

namespace Overblog\GraphQLBundle\ExpressionLanguage;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheInterface;

class ExpressionLanguage extends BaseExpressionLanguage
{
    use ContainerAwareTrait;

    /**
     * ExpressionLanguage constructor.
     *
     * @param CacheItemPoolInterface|ParserCacheInterface|null $parser
     * @param array                                            $providers
     */
    public function __construct($parser = null, array $providers = [])
    {
        // prepend the default provider to let users override it easily
        array_unshift($providers, new ConfigExpressionProvider());
        array_unshift($providers, new AuthorizationExpressionProvider());

        parent::__construct($parser, $providers);
    }

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
