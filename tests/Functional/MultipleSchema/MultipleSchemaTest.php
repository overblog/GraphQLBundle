<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\MultipleSchema;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class MultipleSchemaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel(['test_case' => 'multipleSchema']);
    }

    public function testPublicSchema(): void
    {
        $result = $this->executeGraphQLRequest('{foo}', [], 'public');
        $this->assertSame('foo', $result['data']['foo']);
        $this->assertSchemaQueryTypeName('PublicQuery');
    }

    public function testInternalSchema(): void
    {
        $result = $this->executeGraphQLRequest('{bar foo}', [], 'internal');
        $this->assertSame('bar', $result['data']['bar']);
        $this->assertSame('foo', $result['data']['foo']);
        $this->assertSchemaQueryTypeName('InternalQuery');
    }

    private function assertSchemaQueryTypeName($typeName): void
    {
        $query = $this->getContainer()->get('overblog_graphql.type_resolver')->resolve($typeName);
        $this->assertSame('Query', $query->name);
    }
}
