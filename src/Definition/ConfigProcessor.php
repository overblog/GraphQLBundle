<?php

namespace Overblog\GraphQLBundle\Definition;

use Overblog\GraphQLBundle\Definition\ConfigProcessor\ConfigProcessorInterface;

final class ConfigProcessor implements ConfigProcessorInterface
{
    /**
     * @var ConfigProcessorInterface[]
     */
    private $orderedProcessors;

    /**
     * @var array
     */
    private $processors;

    /**
     * @var bool
     */
    private $isInitialized = false;

    public function addConfigProcessor(ConfigProcessorInterface $configProcessor, $priority = 0)
    {
        $this->register($configProcessor, $priority);
    }

    public function register(ConfigProcessorInterface $configProcessor, $priority = 0)
    {
        if ($this->isInitialized) {
            throw new \LogicException('Registering config processor after calling process() is not supported.');
        }
        $this->processors[] = ['processor' => $configProcessor, 'priority' => $priority];
    }

    public function getOrderedProcessors()
    {
        $this->initialize();

        return $this->orderedProcessors;
    }

    public function process(LazyConfig $lazyConfig)
    {
        foreach ($this->getOrderedProcessors() as $processor) {
            $lazyConfig = $processor->process($lazyConfig);
        }

        return $lazyConfig;
    }

    private function initialize()
    {
        if (!$this->isInitialized) {
            // order processors by DESC priority
            $processors = $this->processors;
            \usort($processors, function ($processorA, $processorB) {
                if ($processorA['priority'] === $processorB['priority']) {
                    return 0;
                }

                return ($processorA['priority'] < $processorB['priority']) ? 1 : -1;
            });

            $this->orderedProcessors = \array_column($processors, 'processor');
            $this->isInitialized = true;
        }
    }
}
