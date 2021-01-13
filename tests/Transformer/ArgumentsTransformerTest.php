<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Transformer;

use Generator;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Overblog\GraphQLBundle\Transformer\ArgumentsTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\RecursiveValidator;

class ArgumentsTransformerTest extends TestCase
{
    private function getTransformer(array $classesMap = null, $validateReturn = null): ArgumentsTransformer
    {
        $validator = $this->createMock(RecursiveValidator::class);
        $validator->method('validate')->willReturn($validateReturn ?: []);

        return new ArgumentsTransformer($validator, $classesMap);
    }

    public function getResolveInfo($types): ResolveInfo
    {
        $info = $this->getMockBuilder(ResolveInfo::class)->disableOriginalConstructor()->getMock();
        $info->schema = new Schema(['types' => $types]);

        return $info;
    }

    protected function getTypes()
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
                'field3' => Type::nonNull($t3),
            ],
        ]);

        return [$t1, $t2, $t3];
    }

    public function testPopulating(): void
    {
        $builder = $this->getTransformer([
            'InputType1' => ['type' => 'input', 'class' => 'Overblog\GraphQLBundle\Tests\Transformer\InputType1'],
            'InputType2' => ['type' => 'input', 'class' => 'Overblog\GraphQLBundle\Tests\Transformer\InputType2'],
        ]);

        $info = $this->getResolveInfo($this->getTypes());

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

        $builder = $this->getTransformer([
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
        $this->assertIsInt($res5[3]);
        $this->assertEquals($res5[4], 'test_string');

        $data = [];
        $res6 = $builder->getInstanceAndValidate('InputType1', $data, $info, 'input1');
        $this->assertInstanceOf(InputType1::class, $res6);

        $res7 = $builder->getInstanceAndValidate('InputType2', ['field3' => 'enum1'], $info, 'input2');
        $this->assertInstanceOf(Enum1::class, $res7->field3);
        $this->assertEquals('enum1', $res7->field3->value);
    }

    public function testRaisedErrors(): void
    {
        $violation = new ConstraintViolation('validation_error', 'validation_error', [], 'invalid', 'field2', 'invalid');
        $builder = $this->getTransformer([
            'InputType1' => ['type' => 'input', 'class' => 'Overblog\GraphQLBundle\Tests\Transformer\InputType1'],
            'InputType2' => ['type' => 'input', 'class' => 'Overblog\GraphQLBundle\Tests\Transformer\InputType2'],
        ], new ConstraintViolationList([$violation]));

        $mapping = ['input1' => 'InputType1', 'input2' => 'InputType2'];
        $data = [
            'input1' => ['field1' => 'hello', 'field2' => 12, 'field3' => true],
            'input2' => ['field1' => [['field1' => 'hello1'], ['field1' => 'hello2']], 'field2' => 12],
        ];

        try {
            $res = $builder->getArguments($mapping, $data, $this->getResolveInfo($this->getTypes()));
            $this->fail("When input data validation fail, it should raise an Overblog\GraphQLBundle\Error\InvalidArgumentsError exception");
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Overblog\GraphQLBundle\Error\InvalidArgumentsError::class, $e);
            $first = $e->getErrors()[0];
            $this->assertInstanceOf(\Overblog\GraphQLBundle\Error\InvalidArgumentError::class, $first);
            $this->assertEquals($first->getErrors()->get(0), $violation);
            $this->assertEquals($first->getName(), 'input1');

            $expected = [
                'input1' => [[
                    'path' => 'field2',
                    'message' => 'validation_error',
                    'code' => null,
                ]],
                'input2' => [[
                    'path' => 'field2',
                    'message' => 'validation_error',
                    'code' => null,
                ]],
            ];

            $this->assertEquals($e->toState(), $expected);
        }
    }

    public function getWrappedInputObject(): Generator
    {
        $inputObject = new InputObjectType([
            'name' => 'InputType1',
            'fields' => [
                'field1' => Type::string(),
                'field2' => Type::int(),
                'field3' => Type::boolean(),
            ],
        ]);
        yield [$inputObject, false];
        yield [new NonNull($inputObject), false];
    }

    /** @dataProvider getWrappedInputObject */
    public function testInputObjectWithWrappingType(Type $type): void
    {
        $transformer = $this->getTransformer([
                'InputType1' => ['type' => 'input', 'class' => InputType1::class],
            ], new ConstraintViolationList()
        );
        $info = $this->getResolveInfo(self::getTypes());

        $data = ['field1' => 'hello', 'field2' => 12, 'field3' => true];

        $inputValue = $transformer->getInstanceAndValidate($type->toString(), $data, $info, 'input1');

        /* @var InputType1 $inputValue */
        $this->assertInstanceOf(InputType1::class, $inputValue);
        $this->assertEquals($inputValue->field1, $data['field1']);
        $this->assertEquals($inputValue->field2, $data['field2']);
        $this->assertEquals($inputValue->field3, $data['field3']);
    }

    public function getWrappedInputObjectList(): Generator
    {
        $inputObject = new InputObjectType([
            'name' => 'InputType1',
            'fields' => [
                'field1' => Type::string(),
                'field2' => Type::int(),
                'field3' => Type::boolean(),
            ],
        ]);
        yield [new ListOfType($inputObject)];
        yield [new ListOfType(new NonNull($inputObject))];
        yield [new NonNull(new ListOfType($inputObject))];
        yield [new NonNull(new ListOfType(new NonNull($inputObject)))];
    }

    /** @dataProvider getWrappedInputObjectList */
    public function testInputObjectWithWrappingTypeList(Type $type): void
    {
        $transformer = $this->getTransformer(
            ['InputType1' => ['type' => 'input', 'class' => InputType1::class]],
            new ConstraintViolationList()
        );
        $info = $this->getResolveInfo(self::getTypes());

        $data = ['field1' => 'hello', 'field2' => 12, 'field3' => true];

        $inputValue = $transformer->getInstanceAndValidate($type->toString(), [$data], $info, 'input1');
        $inputValue = \reset($inputValue);

        /* @var InputType1 $inputValue */
        $this->assertInstanceOf(InputType1::class, $inputValue);
        $this->assertEquals($inputValue->field1, $data['field1']);
        $this->assertEquals($inputValue->field2, $data['field2']);
        $this->assertEquals($inputValue->field3, $data['field3']);
    }
}
