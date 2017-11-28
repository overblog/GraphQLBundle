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
            $config['config'] = isset($config['config']) && is_array($config['config']) ? $config['config'] : [];
            $config['config']['name'] = $name;
        }

        return $configs;
    }
}
