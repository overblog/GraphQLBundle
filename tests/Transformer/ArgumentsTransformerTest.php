<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Transformer;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Overblog\GraphQLBundle\Transformer\ArgumentsTransformer;
use PHPUnit\Framework\TestCase;

class ArgumentsTransformerTest extends TestCase
{
    /**
     * @return ArgumentsTransformer
     */
    private function getBuilder(array $classesMap = null): ArgumentsTransformer
    {
        $validator = $this->createMock(\Symfony\Component\Validator\Validator\RecursiveValidator::class);
        $validator->method('validate')->willReturn([]);

        return new ArgumentsTransformer($validator, $classesMap);
    }

    public function getResolveInfo($types): ResolveInfo
    {
        $info = new ResolveInfo([]);
        $info->schema = new Schema(['types' => $types]);

        return $info;
    }

    public function testPopulating(): void
    {
        $t1 = new InputObjectType([
            'name' => 'InputType1',
            'fields' => [
                'field1' => Type::string(),
                'field2' => Type::int(),
                'field3' => Type::boolean(),
            ],
        ]);

        $t3 = new EnumType([
            'name' => 'Enum1',
            'values' => ['op1' => 1, 'op2' => 2, 'op3' => 3],
        ]);

        $t2 = new InputObjectType([
            'name' => 'InputType2',
            'fields' => [
                'field1' => Type::listOf($t1),
                'field2' => $t3,
            ],
        ]);

        $types = [$t1, $t2, $t3];

        $builder = $this->getBuilder([
            'InputType1' => ['type' => 'input', 'class' => 'Overblog\GraphQLBundle\Tests\Transformer\InputType1'],
            'InputType2' => ['type' => 'input', 'class' => 'Overblog\GraphQLBundle\Tests\Transformer\InputType2'],
        ]);

        $info = $this->getResolveInfo($types);

        $data = [
            'field1' => 'hello',
            'field2' => 12,
            'field3' => true,
        ];

        $res = $builder->getInstanceAndValidate('InputType1', $data, $info, 'input1');

        $this->assertInstanceOf(InputType1::class, $res);
        $this->assertEquals($res->field1, $data['field1']);
        $this->assertEquals($res->field2, $data['field2']);
        $this->assertEquals($res->field3, $data['field3']);

        $data = [
            'field1' => [
                ['field1' => 'hello2', 'field2' => 2, 'field3' => false],
                ['field1' => 'world2'],
            ],
            'field2' => 3,
        ];

        $res2 = $builder->getInstanceAndValidate('InputType2', $data, $info, 'input2');

        $this->assertInstanceOf(InputType2::class, $res2);
        $this->assertTrue(\is_array($res2->field1));
        $this->assertArrayHasKey(0, $res2->field1);
        $this->assertArrayHasKey(1, $res2->field1);
        $this->assertInstanceOf(InputType1::class, $res2->field1[0]);
        $this->assertInstanceOf(InputType1::class, $res2->field1[1]);

        $res3 = $builder->getInstanceAndValidate('Enum1', 2, $info, 'enum1');

        $this->assertEquals(2, $res3);

        $builder = $this->getBuilder([
            'InputType1' => ['type' => 'input', 'class' => 'Overblog\GraphQLBundle\Tests\Transformer\InputType1'],
            'InputType2' => ['type' => 'input', 'class' => 'Overblog\GraphQLBundle\Tests\Transformer\InputType2'],
            'Enum1' => ['type' => 'enum', 'class' => 'Overblog\GraphQLBundle\Tests\Transformer\Enum1'],
        ]);

        $res4 = $builder->getInstanceAndValidate('Enum1', 2, $info, 'enum1');
        $this->assertInstanceOf(Enum1::class, $res4);
        $this->assertEquals(2, $res4->value);

        $mapping = ['input1' => 'InputType1', 'input2' => 'InputType2', 'enum1' => 'Enum1', 'int1' => 'Int!', 'string1' => 'String!'];
        $data = [
            'input1' => ['field1' => 'hello', 'field2' => 12, 'field3' => true],
            'input2' => ['field1' => [['field1' => 'hello1'], ['field1' => 'hello2']], 'field2' => 12],
            'enum1' => 2,
            'int1' => 14,
            'string1' => 'test_string',
        ];

        $res5 = $builder->getArguments($mapping, $data, $info);
        $this->assertInstanceOf(InputType1::class, $res5[0]);
        $this->assertInstanceOf(InputType2::class, $res5[1]);
        $this->assertInstanceOf(Enum1::class, $res5[2]);
        $this->assertEquals(2, \count($res5[1]->field1));
        $this->assertInternalType('int', $res5[3]);
        $this->assertEquals($res5[4], 'test_string');
    }
}
