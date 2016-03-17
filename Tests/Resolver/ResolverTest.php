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

use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Resolver\Resolver;

class ResolverTest extends \PHPUnit_Framework_TestCase
{
    private $privatePropertyWithoutGetter = 'ImNotAccessibleFromOutside:D';

    private $privatePropertyWithGetter = 'IfYouWantMeUseMyGetter';

    private $private_property_with_getter2 = 'IfYouWantMeUseMyGetter2';

    public $name = 'public';

    public $closureProperty;

    public function setUp()
    {
        $this->closureProperty = function () {
            return $this->privatePropertyWithoutGetter;
        };
    }

    /**
     * @param $fieldName
     * @param $source
     * @param $expected
     *
     * @dataProvider resolverProvider
     */
    public function testDefaultResolveFn($fieldName, $source, $expected)
    {
        $info = new ResolveInfo(['fieldName' => $fieldName]);

        $this->assertEquals($expected, Resolver::defaultResolveFn($source, [], $info));
    }

    public function resolverProvider()
    {
        return [
            ['key', ['key' => 'toto'], 'toto'],
            ['fake', ['coco'], null],
            ['privatePropertyWithoutGetter', $this, null],
            ['privatePropertyWithGetter', $this, $this->privatePropertyWithGetter],
            ['private_property_with_getter2', $this, $this->private_property_with_getter2],
            ['not_object_or_array', 'String', null],
            ['name', $this, $this->name],
        ];
    }

    /**
     * @return string
     */
    public function getPrivatePropertyWithGetter()
    {
        return $this->privatePropertyWithGetter;
    }

    /**
     * @return string
     */
    public function getPrivatePropertyWithGetter2()
    {
        return $this->private_property_with_getter2;
    }
}
