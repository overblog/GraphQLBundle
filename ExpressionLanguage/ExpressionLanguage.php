<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\ExpressionLanguage;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ExpressionLanguage extends BaseExpressionLanguage
{
    use ContainerAwareTrait;

    public function __construct(ParserCacheInterface $parser = null, array $providers = [])
    {
        // prepend the default provider to let users override it easily
        array_unshift($providers, new ConfigExpressionProvider());
        array_unshift($providers, new AuthorizationExpressionProvider());

        parent::__construct($parser, $providers);
    }

    public function evaluate($expression, $values = [])
    {
        $this->addValues($values);

        return parent::evaluate($expression, $values);
    }

    private function addValues(&$values)
    {
        $values['container'] = $this->container;

        if ($this->container->has('request_stack')) {
            $values['request'] = $this->container->get('request_stack')->getCurrentRequest();
        }

        if ($this->container->has('security.token_storage')) {
            $values['token'] = $this->container->get('security.token_storage')->getToken();
            if ($values['token'] instanceof TokenInterface) {
                $values['user'] = $values['token']->getUser();
            }
        }
    }
}
