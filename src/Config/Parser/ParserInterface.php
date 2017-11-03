<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
