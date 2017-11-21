<?php

namespace Overblog\GraphQLBundle\Event;

use GraphQL\Executor\ExecutionResult;
use Symfony\Component\EventDispatcher\Event;

class ExecutorResultEvent extends Event
{
    /** @var ExecutionResult */
    private $result;

    /** @var \ArrayObject */
    private $contextValue;

    public function __construct(ExecutionResult $result, \ArrayObject $contextValue)
    {
        $this->result = $result;
        $this->contextValue = $contextValue;
    }

    /**
     * @return ExecutionResult
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return \ArrayObject
     */
    public function getContextValue()
    {
        return $this->contextValue;
    }
}
