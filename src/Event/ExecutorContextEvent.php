<?php

namespace Overblog\GraphQLBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

// TODO(mcg-web): remove hack after migrating Symfony >= 4.3
if (EventDispatcherVersionHelper::isForLegacy()) {
    final class ExecutorContextEvent extends \Symfony\Component\EventDispatcher\Event
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
} else {
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
}
