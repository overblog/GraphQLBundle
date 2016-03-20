<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Resolver;

use Overblog\GraphQLBundle\Resolver\FieldResolver;

class FieldResolverTest extends AbstractSimpleResolverTest
{
    protected function createResolver()
    {
        return new FieldResolver();
    }
}
