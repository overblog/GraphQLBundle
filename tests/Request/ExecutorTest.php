<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Request;

use GraphQL\Executor\Promise\Adapter\ReactPromiseAdapter;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Overblog\GraphQLBundle\Definition\Type\ExtensibleSchema;
use Overblog\GraphQLBundle\Executor\Executor;
use Overblog\GraphQLBundle\Request\Executor as RequestExecutor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class ExecutorTest extends TestCase
{
    protected function getMockedExecutor(): RequestExecutor
    {
        /** @var EventDispatcher&MockObject $dispatcher */
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)->onlyMethods(['dispatch'])->getMock();

        return new RequestExecutor(new Executor(), new ReactPromiseAdapter(), $dispatcher);
    }

    public function testGetSchemaNoSchemaFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('At least one schema should be declared.');

        $this->getMockedExecutor()->getSchema('fake');
    }

    public function testGetSchemasName(): void
    {
        $executor = $this->getMockedExecutor();
        $executor->addSchemaBuilder('schema1', function (): void {
        });
        $executor->addSchema('schema2', new Schema([]));

        $this->assertSame($executor->getSchemasNames(), ['schema1', 'schema2']);
    }

    private function createExtensibleSchema(bool $resettable = false): ExtensibleSchema
    {
        $schema = new ExtensibleSchema([
            'query' => new ObjectType(['name' => 'Query', 'fields' => ['id' => Type::string()]]),
        ]);
        $schema->setIsResettable($resettable);

        return $schema;
    }

    public function testResetRemovesResettableExtensibleSchema(): void
    {
        $executor = $this->getMockedExecutor();
        $resettable = $this->createExtensibleSchema(true);
        $executor->addSchema('resettable', $resettable);
        // Add a dummy builder so executor doesn't throw "no schema declared"
        $executor->addSchemaBuilder('dummy', fn () => $this->createExtensibleSchema(false));

        $executor->reset();

        // After reset the resettable schema is gone; getSchema throws NotFoundHttpException
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $executor->getSchema('resettable');
    }

    public function testResetRemovesPlainSchema(): void
    {
        $executor = $this->getMockedExecutor();
        $plain = new Schema([]);
        $executor->addSchema('plain', $plain);
        // Add a dummy builder so executor doesn't throw "no schema declared"
        $executor->addSchemaBuilder('dummy', fn () => $this->createExtensibleSchema(false));

        $executor->reset();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $executor->getSchema('plain');
    }

    public function testResetKeepsNonResettableExtensibleSchema(): void
    {
        $executor = $this->getMockedExecutor();
        $nonResettable = $this->createExtensibleSchema(false);
        $executor->addSchema('stable', $nonResettable);

        $executor->reset();

        $schema = $executor->getSchema('stable');
        $this->assertSame($nonResettable, $schema, 'Non-resettable ExtensibleSchema should survive reset');
    }

    public function testGetSchemaBuildsFromBuilder(): void
    {
        $executor = $this->getMockedExecutor();
        $built = $this->createExtensibleSchema(false);
        $callCount = 0;
        $executor->addSchemaBuilder('built', function () use ($built, &$callCount): ExtensibleSchema {
            ++$callCount;

            return $built;
        });

        $schema1 = $executor->getSchema('built');
        $schema2 = $executor->getSchema('built');

        $this->assertSame($built, $schema1);
        $this->assertSame($schema1, $schema2);
        $this->assertSame(1, $callCount, 'Builder closure should be called only once');
    }

    public function testGetSchemaDefaultNameFromBuilders(): void
    {
        $executor = $this->getMockedExecutor();
        $schema = $this->createExtensibleSchema(false);
        $executor->addSchemaBuilder('first', fn () => $schema);

        $resolved = $executor->getSchema(null);

        $this->assertSame($schema, $resolved);
    }
}
