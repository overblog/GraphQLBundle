<?php

/*
 * This file is part of the OverblogGraphQLPhpGenerator package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLGenerator\Tests;

class GlobalTest extends TestCase
{
    /**
     * @group cs
     */
    public function testCodeStandard()
    {
        $this->assertCodeStandard(__DIR__ . '/../.');
    }
}
