<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser;

use SplFileInfo;
use Symfony\Component\DependencyInjection\ContainerBuilder;

interface PreParserInterface extends ParserInterface
{
    /**
     * @return mixed
     */
    public static function preParse(SplFileInfo $file, ContainerBuilder $container, array $configs = []);
}
