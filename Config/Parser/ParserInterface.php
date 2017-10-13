<?php

namespace Overblog\GraphQLBundle\Config\Parser;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\SplFileInfo;

interface ParserInterface
{
    /**
     * @param SplFileInfo      $file
     * @param ContainerBuilder $container
     *
     * @return array
     */
    public static function parse(SplFileInfo $file, ContainerBuilder $container);
}
