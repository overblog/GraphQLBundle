<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\EventListener;

use Overblog\GraphQLBundle\Event\ExecutorResultEvent;
use function memory_get_usage;
use function microtime;
use function round;
use function sprintf;

final class DebugListener
{
    private float $startTime;
    private int $startMemoryUsage;

    public function onPreExecutor(): void
    {
        $this->startTime = microtime(true);
        $this->startMemoryUsage = memory_get_usage(true);
    }

    public function onPostExecutor(ExecutorResultEvent $executorResultEvent): void
    {
        $executorResultEvent->getResult()->extensions['debug'] = [
            'executionTime' => sprintf('%d ms', round(microtime(true) - $this->startTime, 3) * 1000),
            'memoryUsage' => sprintf('%.2F MiB', (memory_get_usage(true) - $this->startMemoryUsage) / 1024 / 1024),
        ];
    }
}
