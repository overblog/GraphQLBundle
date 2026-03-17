<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Definition\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\Type\ExtensibleSchema;
use PHPUnit\Framework\TestCase;

final class ExtensibleSchemaTest extends TestCase
{
    private function createSchema(): ExtensibleSchema
    {
        return new ExtensibleSchema([
            'query' => new ObjectType(['name' => 'Query', 'fields' => ['id' => Type::string()]]),
        ]);
    }

    public function testIsResettableDefaultsToFalse(): void
    {
        $schema = $this->createSchema();

        $this->assertFalse($schema->isResettable());
    }

    public function testSetIsResettableTrue(): void
    {
        $schema = $this->createSchema();
        $schema->setIsResettable(true);

        $this->assertTrue($schema->isResettable());
    }

    public function testSetIsResettableFalse(): void
    {
        $schema = $this->createSchema();
        $schema->setIsResettable(true);
        $schema->setIsResettable(false);

        $this->assertFalse($schema->isResettable());
    }
}
