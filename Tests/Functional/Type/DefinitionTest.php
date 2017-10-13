<?php

namespace Overblog\GraphQLBundle\Tests\Functional\Security;

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
        $this->assertEquals([
            'name' => 'foo',
            'description' => null,
            'deprecationReason' => 'Just because',
            'value' => 'foo',
        ], $value->config);
        $this->assertEquals(true, $this->isDeprecated($value));
    }

    public function testDefinesAnObjectTypeWithDeprecatedField()
    {
        /** @var ObjectType $TypeWithDeprecatedField */
        $TypeWithDeprecatedField = $this->getType('ObjectWithDeprecatedField');
        $field = $TypeWithDeprecatedField->getField('bar');
        $this->assertEquals(Type::string(), $field->getType());
        $this->assertEquals(true, $this->isDeprecated($field));
        $this->assertEquals('A terrible reason', $field->deprecationReason);
        $this->assertEquals('bar', $field->name);
        $this->assertEquals([], $field->args);
    }

    private function isDeprecated($node)
    {
        // TODO(mcg-web) refactor after moving to latest lib version
        if (is_callable([$node, 'isDeprecated'])) {
            return $node->isDeprecated();
        }

        return (bool) $node->deprecationReason;
    }

    private function getType($type)
    {
        return $this->getContainer()->get('overblog_graphql.type_resolver')->resolve($type);
    }
}
