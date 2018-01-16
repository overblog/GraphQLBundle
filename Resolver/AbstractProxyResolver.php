<?php

namespace Overblog\GraphQLBundle\Resolver;

use Overblog\GraphQLBundle\Event\Events;
use Overblog\GraphQLBundle\Event\PreResolverEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractProxyResolver extends AbstractResolver
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * AbstractProxyResolver constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        EventDispatcherInterface $dispatcher
    ) {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param $input
     *
     * @return mixed
     */
    public function resolve($input)
    {
        if (!is_array($input)) {
            $input = [$input];
        }

        if (!isset($input[0]) || !isset($input[1])) {
            $optionResolver = new OptionsResolver();
            $optionResolver->setDefaults([null, []]);
            $input = $optionResolver->resolve($input);
        }

        $alias = $input[0];
        $funcArgs = $input[1];

        $solution = $this->getSolution($alias);

        if (null === $solution) {
            throw new UnresolvableException($this->unresolvableMessage($alias));
        }

        $options = $this->getSolutionOptions($alias);
        $func = [$solution, $options['method']];

        $event = new PreResolverEvent($func, $funcArgs);
        $this->dispatcher->dispatch(Events::PRE_RESOLVER, $event);

        return call_user_func_array($func, $funcArgs);
    }

    abstract protected function unresolvableMessage($alias);
}
