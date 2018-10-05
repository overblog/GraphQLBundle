<?php

namespace Overblog\GraphQLBundle\Config\Processor;

final class NamedConfigProcessor implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public static function process(array $configs)
    {
        foreach ($configs as $name => &$config) {
            if (empty($config['class_name'])) {
                $config['class_name'] = \sprintf('%sType', $name);
            }
            if (empty($config['config']['name'])) {
                $config['config']['name'] = $name;
            }
        }

        return $configs;
    }
}
