<?php

namespace Overblog\GraphQLBundle\Resolver;

use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractProxyResolver extends AbstractResolver
{
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

        return call_user_func_array($func, $funcArgs);
    }

    abstract protected function unresolvableMessage($alias);
}
