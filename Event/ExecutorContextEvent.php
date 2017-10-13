<?php

namespace Overblog\GraphQLBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ExecutorContextEvent extends Event
{
    private $executorContext = [];

    public function __construct(array $executorContext)
    {
        $this->executorContext = $executorContext;
    }

    /**
     * @return array
     */
    public function getExecutorContext()
    {
        return $this->executorContext;
    }

    /**
     * @param array $executionContext
     */
    public function setExecutorContext(array $executionContext)
    {
        $this->executorContext = $executionContext;
    }
}
