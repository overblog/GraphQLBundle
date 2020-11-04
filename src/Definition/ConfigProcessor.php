<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition;

use Overblog\GraphQLBundle\Definition\ConfigProcessor\ConfigProcessorInterface;

final class ConfigProcessor
{
    /**
     * @var ConfigProcessorInterface[]
     */
    private array $processors;

    public function __construct(iterable $processors)
    {
        foreach ($processors as $processor) {
            $this->register($processor);
        }
    }

    public function getProcessors(): array
    {
        return $this->processors;
    }

    public function register(ConfigProcessorInterface $configProcessor): void
    {
        $this->processors[] = $configProcessor;
    }

    public function process(array $config): array
    {
        foreach ($this->processors as $processor) {
            $config = $processor->process($config);
        }

        return $config;
    }
}
