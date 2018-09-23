<?php

namespace Overblog\GraphQLBundle\Tests\Functional\Type;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class DefinitionTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        static::bootKernel(['test_case' => 'definition']);
    }

    public function testDefinesEnumTypeWithDeprecatedValue()
    {
        /** @var EnumType $enumTypeWithDeprecatedValue */
        $enumTypeWithDeprecatedValue = $this->getType('EnumWithDeprecatedValue');
        $value = $enumTypeWithDeprecatedValue->getValues()[0];
        $this->assertSame([
            'name' => 'foo',
            'value' => 'foo',
            'deprecationReason' => 'Just because',
            'description' => null,
        ], $value->config);
        $this->assertTrue($value->isDeprecated());
    }

    public function testDefinesAnObjectTypeWithDeprecatedField()
    {
        /** @var ObjectType $TypeWithDeprecatedField */
        $TypeWithDeprecatedField = $this->getType('ObjectWithDeprecatedField');
        $field = $TypeWithDeprecatedField->getField('bar');
        $this->assertSame(Type::string(), $field->getType());
        $this->assertTrue($field->isDeprecated());
        $this->assertSame('A terrible reason', $field->deprecationReason);
        $this->assertSame('bar', $field->name);
        $this->assertSame([], $field->args);
    }

    private function getType($type)
    {
        return $this->getContainer()->get('overblog_graphql.type_resolver')->resolve($type);
    }
}
