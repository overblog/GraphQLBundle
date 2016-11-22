<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\DependencyInjection\Compiler;

/**
 * Class FakeInjectedService.
 */
class FakeInjectedService
{
    public function doSomething()
    {
        return true;
    }
}
