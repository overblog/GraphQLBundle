<?php

namespace Overblog\GraphQLBundle\Definition\ConfigProcessor;

use Overblog\GraphQLBundle\Definition\LazyConfig;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class InjectServicesConfigProcessor implements ConfigProcessorInterface
{
    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function process(LazyConfig $lazyConfig)
    {
        $vars = $lazyConfig->getVars();
        $container = $this->container;
        $user = null;
        $request = null;
        $token = null;
        if ($container->has('request_stack')) {
            $request = $container->get('request_stack')->getCurrentRequest();
        }
        if ($container->has('security.token_storage')) {
            $token = $container->get('security.token_storage')->getToken();
            if ($token instanceof TokenInterface) {
                $user = $token->getUser();
            }
        }
        $vars['token'] = $token;
        $vars['container'] = $container;
        $vars['user'] = $user;
        $vars['request'] = $request;

        return $lazyConfig;
    }
}
