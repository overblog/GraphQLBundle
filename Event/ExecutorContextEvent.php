<?php

namespace Overblog\GraphQLBundle\Event;

use Symfony\Component\EventDispatcher\Event;

final class ExecutorContextEvent extends Event
{
    /** @var \ArrayObject */
    private $executorContext;

    /**
     * @param \ArrayObject $executorContext
     */
    public function __construct(\ArrayObject $executorContext)
    {
        $this->executorContext = $executorContext;
    }

    /**
     * @return \ArrayObject
     */
    public function getExecutorContext()
    {
        return $this->executorContext;
    }
}
