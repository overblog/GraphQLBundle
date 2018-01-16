<?php

namespace Overblog\GraphQLBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class PreResolverEvent extends Event
{
    /** @var array */
    private $func;

    /** @var array */
    private $funcArgs;

    /**
     * PreResolverEvent constructor.
     *
     * @param $func
     * @param $funcArgs
     */
    public function __construct($func, $funcArgs)
    {
        $this->func = $func;
        $this->funcArgs = $funcArgs;
    }

    /**
     * @return array
     */
    public function getFunc()
    {
        return $this->func;
    }

    /**
     * @return array
     */
    public function getFuncArgs()
    {
        return $this->funcArgs;
    }
}