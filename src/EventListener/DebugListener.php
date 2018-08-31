<?php

namespace Overblog\GraphQLBundle\EventListener;

use Overblog\GraphQLBundle\Event\ExecutorResultEvent;

final class DebugListener
{
    /** @var float */
    private $startTime;

    /** @var int */
    private $startMemoryUsage;

    public function onPreExecutor()
    {
        $this->startTime = \microtime(true);
        $this->startMemoryUsage = \memory_get_usage(true);
    }

    public function onPostExecutor(ExecutorResultEvent $executorResultEvent)
    {
        $executorResultEvent->getResult()->extensions['debug'] = [
            'executionTime' => \sprintf('%d ms', \round(\microtime(true) - $this->startTime, 3) * 1000),
            'memoryUsage' => \sprintf('%.2F MiB', (\memory_get_usage(true) - $this->startMemoryUsage) / 1024 / 1024),
        ];
    }
}
