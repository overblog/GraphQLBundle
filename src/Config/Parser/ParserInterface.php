<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser;

use SplFileInfo;
use Symfony\Component\DependencyInjection\ContainerBuilder;

interface ParserInterface
{
    /**
     * @param array<string,mixed> $configs
     *
     * @return array<string,mixed>
     */
    public static function parse(SplFileInfo $file, ContainerBuilder $container, array $configs = []): array;
}
