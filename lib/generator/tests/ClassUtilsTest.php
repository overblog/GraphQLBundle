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

use Overblog\GraphQLGenerator\ClassUtils;

class ClassUtilsTest extends TestCase
{
    /**
     * @param $code
     * @param $expected
     *
     * @dataProvider shortenClassFromCodeDataProvider
     */
    public function testShortenClassFromCode($code, $expected)
    {
        $actual = ClassUtils::shortenClassFromCode($code);

        $this->assertEquals($expected, $actual);
    }

    public function shortenClassFromCodeDataProvider()
    {
        return [
            ['$toto, \Toto\Tata $test', '$toto, Tata $test'],
            ['\Tata $test', 'Tata $test'],
        ];
    }
}
