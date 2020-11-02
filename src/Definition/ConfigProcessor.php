<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition;

use Closure;
use Overblog\GraphQLBundle\Definition\ConfigProcessor\ConfigProcessorInterface;

final class ConfigProcessor
{
    /**
     * @var ConfigProcessorInterface[]
     */
    private array $processors;
    private GlobalVariables $globalVariables;

    public function __construct(iterable $processors, GlobalVariables $globalVariables)
    {
        foreach ($processors as $processor) {
            $this->register($processor);
        }
        $this->globalVariables = $globalVariables;
    }

    public function getProcessors(): array
    {
        return $this->processors;
    }

    public function register(ConfigProcessorInterface $configProcessor): void
    {
        $this->processors[] = $configProcessor;
    }

    public function process(Closure $loader): array
    {
        $lazyConfig = LazyConfig::create($loader, $this->globalVariables);

        foreach ($this->getProcessors() as $processor) {
            $lazyConfig = $processor->process($lazyConfig);
        }

        return $lazyConfig->load();
    }
}
