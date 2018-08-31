<?php

namespace Overblog\GraphQLBundle\Definition;

final class GlobalVariables
{
    /** @var array */
    private $services;

    public function __construct(array $services = [])
    {
        $this->services = $services;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function get($name)
    {
        if (!isset($this->services[$name])) {
            throw new \LogicException(\sprintf('Global variable %s could not be located. You should define it.', \json_encode($name)));
        }

        return $this->services[$name];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return isset($this->services[$name]);
    }
}
