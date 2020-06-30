<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Processor;

use function sprintf;

final class NamedConfigProcessor implements ProcessorInterface
{
    public static function process(array $configs): array
    {
        foreach ($configs as $name => &$config) {
            if (empty($config['class_name'])) {
                $config['class_name'] = sprintf('%sType', $name);
            }
            if (empty($config['config']['name'])) {
                $config['config']['name'] = $name;
            }
        }

        return $configs;
    }
}
