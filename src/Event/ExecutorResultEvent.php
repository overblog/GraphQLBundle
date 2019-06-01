<?php

namespace Overblog\GraphQLBundle\Event;

use GraphQL\Executor\ExecutionResult;
use Symfony\Contracts\EventDispatcher\Event;

// TODO(mcg-web): remove hack after migrating Symfony >= 4.3
if (EventDispatcherVersionHelper::isForLegacy()) {
    final class ExecutorResultEvent extends \Symfony\Component\EventDispatcher\Event
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
} else {
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
}
