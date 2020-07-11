<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Event;

use GraphQL\Executor\ExecutionResult;
use Symfony\Contracts\EventDispatcher\Event;

final class ExecutorResultEvent extends Event
{
    private ExecutionResult $result;

    /** @var ExecutorArgumentsEvent */
    private $executorArguments;

    public function __construct(ExecutionResult $result, ExecutorArgumentsEvent $executorArguments)
    {
        $this->result = $result;
        $this->executorArguments = $executorArguments;
    }

    public function getResult(): ExecutionResult
    {
        return $this->result;
    }

    public function getExecutorArguments(): ExecutorArgumentsEvent
    {
        return $this->executorArguments;
    }
}
