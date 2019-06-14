<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

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
    public function getExecutorContext(): \ArrayObject
    {
        return $this->executorContext;
    }
}
