<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Request;

use GraphQL\Executor\Promise\Adapter\ReactPromiseAdapter;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Schema;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\DisableIntrospection;
use Overblog\GraphQLBundle\Executor\Executor;
use Overblog\GraphQLBundle\Request\Executor as RequestExecutor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class ExecutorTest extends TestCase
{
    private const INTROSPECTION_QUERY = '{ __schema { queryType { name } } }';

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

    public function testIntrospectionCompatibilityMethodsToggleValidation(): void
    {
        $executor = $this->getMockedExecutor();
        $schema = new Schema([
            'query' => new ObjectType([
                'name' => 'Query',
                'fields' => [
                    'value' => ['type' => 'String'],
                ],
            ]),
        ]);
        $document = Parser::parse(self::INTROSPECTION_QUERY);

        try {
            $executor->disableIntrospectionQuery();
            $errors = DocumentValidator::validate($schema, $document);
            self::assertCount(1, $errors);
            self::assertSame(DisableIntrospection::introspectionDisabledMessage(), $errors[0]->getMessage());

            $executor->enableIntrospectionQuery();
            self::assertSame([], DocumentValidator::validate($schema, $document));
        } finally {
            $executor->enableIntrospectionQuery();
        }
    }
}
