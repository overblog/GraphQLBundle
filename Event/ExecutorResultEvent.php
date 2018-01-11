<?php

namespace Overblog\GraphQLBundle\Event;

use GraphQL\Executor\ExecutionResult;
use Symfony\Component\EventDispatcher\Event;

final class ExecutorResultEvent extends Event
{
    /** @var ExecutionResult */
    private $result;

    public function __construct(ExecutionResult $result)
    {
        $this->result = $result;
    }

    /**
     * @return ExecutionResult
     */
    public function getResult()
    {
        return $this->result;
    }
}
