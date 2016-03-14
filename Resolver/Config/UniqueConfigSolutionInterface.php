<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Resolver\Config;

interface UniqueConfigSolutionInterface extends ConfigSolutionInterface
{
    /**
     * @param  mixed              $values
     * @param  null|array|\ArrayAccess $config
     * @return mixed              $value
     */
    public function solve($values, $config = null);
}
