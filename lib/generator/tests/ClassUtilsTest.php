<?php declare(strict_types=1);

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
     * @param string $code
     * @param string $expected
     *
     * @dataProvider shortenClassFromCodeDataProvider
     */
    public function testShortenClassFromCode(string $code, string $expected): void
    {
        $actual = ClassUtils::shortenClassFromCode($code);

        $this->assertEquals($expected, $actual);
    }

    public function shortenClassFromCodeDataProvider(): iterable
    {
        yield ['$toto, \Toto\Tata $test', '$toto, Tata $test'];
        yield ['\Tata $test', 'Tata $test'];
    }
}
