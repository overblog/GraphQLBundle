<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition;

use Overblog\GraphQLBundle\Definition\ConfigProcessor\ConfigProcessorInterface;

final class ConfigProcessor implements ConfigProcessorInterface
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

    public function process(LazyConfig $lazyConfig): LazyConfig
    {
        foreach ($this->getProcessors() as $processor) {
            $lazyConfig = $processor->process($lazyConfig);
        }

        return $lazyConfig;
    }
}
