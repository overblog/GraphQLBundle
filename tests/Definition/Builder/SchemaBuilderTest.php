<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Definition\Builder;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\Builder\SchemaBuilder;
use Overblog\GraphQLBundle\Definition\Type\ExtensibleSchema;
use Overblog\GraphQLBundle\Resolver\TypeResolver;
use PHPUnit\Framework\TestCase;

final class SchemaBuilderTest extends TestCase
{
    private function createSchemaBuilder(): SchemaBuilder
    {
        $typeResolver = new TypeResolver();
        $typeResolver->addSolution(
            'RootQuery',
            fn () => new ObjectType(['name' => 'RootQuery', 'fields' => ['id' => Type::string()]])
        );

        return new SchemaBuilder($typeResolver);
    }

    public function testGetBuilderCachesByName(): void
    {
        $builder = $this->createSchemaBuilder();
        $closure = $builder->getBuilder('default', 'RootQuery');

        $schema1 = $closure();
        $schema2 = $closure();

        $this->assertSame($schema1, $schema2, 'getBuilder should return the same schema instance on repeated calls');
    }

    public function testResetRemovesResettableSchemas(): void
    {
        $builder = $this->createSchemaBuilder();

        $resettableClosure = $builder->getBuilder('resettable', 'RootQuery', resettable: true);
        $nonResettableClosure = $builder->getBuilder('stable', 'RootQuery', resettable: false);

        $resettableBefore = $resettableClosure();
        $nonResettableBefore = $nonResettableClosure();

        $builder->reset();

        $resettableAfter = $resettableClosure();
        $nonResettableAfter = $nonResettableClosure();

        $this->assertNotSame($resettableBefore, $resettableAfter, 'Resettable schema should be rebuilt after reset');
        $this->assertSame($nonResettableBefore, $nonResettableAfter, 'Non-resettable schema should be the same instance after reset');
    }

    public function testResetKeepsNonResettableSchemas(): void
    {
        $builder = $this->createSchemaBuilder();
        $closure = $builder->getBuilder('stable', 'RootQuery', resettable: false);

        $schemaBefore = $closure();
        $builder->reset();
        $schemaAfter = $closure();

        $this->assertSame($schemaBefore, $schemaAfter);
        $this->assertInstanceOf(ExtensibleSchema::class, $schemaAfter);
        $this->assertFalse($schemaAfter->isResettable());
    }

    public function testCreateSetsResettableFlag(): void
    {
        $builder = $this->createSchemaBuilder();

        $resettableSchema = $builder->create('r', 'RootQuery', resettable: true);
        $nonResettableSchema = $builder->create('n', 'RootQuery', resettable: false);

        $this->assertTrue($resettableSchema->isResettable());
        $this->assertFalse($nonResettableSchema->isResettable());
    }
}
