<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Resolver;

use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Resolver\FieldResolver;
use PHPUnit\Framework\TestCase;

class ResolverFieldTest extends TestCase
{
    /**
     * @dataProvider resolverProvider
     *
     * @param mixed $source
     */
    public function testDefaultFieldResolveFn(string $fieldName, $source, ?string $expected): void
    {
        $info = $this->getMockBuilder(ResolveInfo::class)->disableOriginalConstructor()->getMock();
        $info->fieldName = $fieldName;

        $this->assertSame($expected, (new FieldResolver())($source, [], [], $info));
    }

    public function resolverProvider(): array
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
