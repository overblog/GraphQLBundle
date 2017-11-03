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
use PHPUnit\Framework\TestCase;

class ResolverTest extends TestCase
{
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

        $this->assertEquals($expected, Resolver::defaultResolveFn($source, [], [], $info));
    }

    public function resolverProvider()
    {
        $object = new Toto();

        return [
            ['key', ['key' => 'toto'], 'toto'],
            ['fake', ['coco'], null],
            ['privatePropertyWithoutGetter', $object, null],
            ['privatePropertyWithoutGetterUsingCallBack', $object, Toto::PRIVATE_PROPERTY_WITHOUT_GETTER],
            ['privatePropertyWithGetter', $object, Toto::PRIVATE_PROPERTY_WITH_GETTER_VALUE],
            ['private_property_with_getter2', $object, Toto::PRIVATE_PROPERTY_WITH_GETTER2_VALUE],
            ['not_object_or_array', 'String', null],
            ['name', $object, $object->name],
        ];
    }
}
