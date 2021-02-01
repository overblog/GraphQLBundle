<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Validator;

use ArrayObject;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\ResolverArgs;
use Overblog\GraphQLBundle\Validator\ValidationNode;
use PHPUnit\Framework\TestCase;

class ValidationNodeTest extends TestCase
{
    public function testValidationNode(): void
    {
        $parentType = new ObjectType(['name' => 'Mutation']);
        $parentNode = new ValidationNode($parentType, null, null, $this->createResolveArgs());

        $childType = new ObjectType(['name' => 'createUser']);
        $childNode = new ValidationNode($childType, 'createUser', $parentNode, $this->createResolveArgs());

        $deepestChild = new ObjectType(['name' => 'someField']);
        $deepestNode = new ValidationNode($deepestChild, null, $childNode, $this->createResolveArgs());

        $this->assertSame($parentNode, $childNode->findParent('Mutation'));
        $this->assertSame($parentNode, $deepestNode->findParent('Mutation'));
        $this->assertNull($childNode->findParent('Test'));
        $this->assertEquals('createUser', $childNode->getFieldName());
        $this->assertEquals('createUser', $childNode->getName());
        $this->assertSame($parentNode, $childNode->getParent());
        $this->assertTrue($childNode->getResolverArg('value'));
        $this->assertInstanceOf(ResolveInfo::class, $childNode->getResolverArg('info'));
        $this->assertInstanceOf(ArrayObject::class, $childNode->getResolverArg('context'));
        $this->assertTrue($childNode->getResolverArg('value'));
        $this->assertNull($childNode->getResolverArg('test'));

        $this->assertSame($childType, $childNode->getType());
    }

    private function createResolveArgs(): ResolverArgs
    {
        return new ResolverArgs(
            true,
            new Argument(),
            new ArrayObject(),
            $this->getMockBuilder(ResolveInfo::class)->disableOriginalConstructor()->getMock(),
        );
    }
}
