<?php

namespace Overblog\GraphQLBundle\Definition;

use Overblog\GraphQLBundle\Definition\ConfigProcessor\ConfigProcessorInterface;

final class ConfigProcessor implements ConfigProcessorInterface
{
    /**
     * @var ConfigProcessorInterface[]
     */
    private $processors;

    /**
     * @var bool
     */
    private $isProcessed = false;

    public function addConfigProcessor(ConfigProcessorInterface $configProcessor, $priority = 0)
    {
        $this->register($configProcessor, $priority);
    }

    public function register(ConfigProcessorInterface $configProcessor, $priority = 0)
    {
        if ($this->isProcessed) {
            throw new \LogicException('Registering config processor after calling process() is not supported.');
        }
        $this->processors[] = ['processor' => $configProcessor, 'priority' => $priority];
    }

    public function process(LazyConfig $lazyConfig)
    {
        $this->initialize();
        foreach ($this->processors as $processor) {
            $lazyConfig = $processor->process($lazyConfig);
        }

        return $lazyConfig;
    }

    private function initialize()
    {
        if (!$this->isProcessed) {
            // order processors by DESC priority
            usort($this->processors, function ($processorA, $processorB) {
                if ($processorA['priority'] === $processorB['priority']) {
                    return 0;
                }

                return ($processorA['priority'] < $processorB['priority']) ? 1 : -1;
            });

            $this->processors = array_column($this->processors, 'processor');
            $this->isProcessed = true;
        }
    }
}
