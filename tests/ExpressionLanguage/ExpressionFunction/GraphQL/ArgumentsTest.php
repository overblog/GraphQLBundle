<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\GraphQL;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Schema;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Arguments;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;
use Overblog\GraphQLBundle\Tests\Transformer\ArgumentsTransformerTest;
use Overblog\GraphQLBundle\Tests\Transformer\Enum1;
use Overblog\GraphQLBundle\Tests\Transformer\InputType1;
use Overblog\GraphQLBundle\Tests\Transformer\InputType2;
use Overblog\GraphQLBundle\Transformer\ArgumentsTransformer;
use Symfony\Component\Validator\Validator\RecursiveValidator;

class ArgumentsTest extends TestCase
{
    private $transformer;

    protected function getFunctions()
    {
        $this->transformer = $this->getTransformer([
            'InputType1' => ['type' => 'input', 'class' => 'Overblog\GraphQLBundle\Tests\Transformer\InputType1'],
            'InputType2' => ['type' => 'input', 'class' => 'Overblog\GraphQLBundle\Tests\Transformer\InputType2'],
            'Enum1' => ['type' => 'enum', 'class' => 'Overblog\GraphQLBundle\Tests\Transformer\Enum1'],
        ]);

        return [new Arguments($this->transformer)];
    }

    public function getResolveInfo($types): ResolveInfo
    {
        $info = $this->getMockBuilder(ResolveInfo::class)->disableOriginalConstructor()->getMock();
        $info->schema = new Schema(['types' => $types]);

        return $info;
    }

    private function getTransformer(array $classesMap = null, $validateReturn = null): ArgumentsTransformer
    {
        $validator = $this->createMock(RecursiveValidator::class);
        $validator->method('validate')->willReturn($validateReturn ?: []);

        return new ArgumentsTransformer($validator, $classesMap);
    }

    public function testEvaluator()
    {
        $info = $this->getResolveInfo(ArgumentsTransformerTest::getTypes());

        $mapping = ['input1' => 'InputType1', 'input2' => 'InputType2', 'enum1' => 'Enum1', 'int1' => 'Int!', 'string1' => 'String!'];
        $data = [
            'input1' => ['field1' => 'hello', 'field2' => 12, 'field3' => true],
            'input2' => ['field1' => [['field1' => 'hello1'], ['field1' => 'hello2']], 'field2' => 12],
            'enum1' => 2,
            'int1' => 14,
            'string1' => 'test_string',
        ];

        $res = $this->expressionLanguage->evaluate("arguments(mapping, data, info)", [
            'mapping' => $mapping,
            'data' => $data,
            'info' => $info
        ]);

        $this->assertInstanceOf(InputType1::class, $res[0]);
        $this->assertInstanceOf(InputType2::class, $res[1]);
        $this->assertInstanceOf(Enum1::class, $res[2]);
        $this->assertEquals(2, \count($res[1]->field1));
        $this->assertIsInt($res[3]);
        $this->assertEquals($res[4], 'test_string');

        $data = [];
        $res = $this->transformer->getInstanceAndValidate('InputType1', $data, $info, 'input1');
        $this->assertInstanceOf(InputType1::class, $res);

        $res = $this->transformer->getInstanceAndValidate('InputType2', ['field3' => 'enum1'], $info, 'input2');
        $this->assertInstanceOf(Enum1::class, $res->field3);
        $this->assertEquals('enum1', $res->field3->value);
    }
}
