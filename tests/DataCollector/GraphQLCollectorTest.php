<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\DataCollector;

use ArrayObject;
use GraphQL\Executor\ExecutionResult;
use Overblog\GraphQLBundle\DataCollector\GraphQLCollector;
use Overblog\GraphQLBundle\Definition\Type\ExtensibleSchema;
use Overblog\GraphQLBundle\Event\ExecutorArgumentsEvent;
use Overblog\GraphQLBundle\Event\ExecutorResultEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\Cloner\Data;

class GraphQLCollectorTest extends TestCase
{
    public function testCollect(): void
    {
        $collector = new GraphQLCollector();

        $request = new Request();
        $request->attributes->set('_route_params', ['schemaName' => 'myschema']);

        $collector->onPostExecutor(new ExecutorResultEvent(
            new ExecutionResult(['res' => 'ok', 'error' => 'my error']),
            ExecutorArgumentsEvent::create(new ExtensibleSchema([]), 'invalid', new ArrayObject())
        ));

        $collector->onPostExecutor(new ExecutorResultEvent(
            new ExecutionResult(['res' => 'ok', 'error' => 'my error']),
            ExecutorArgumentsEvent::create(new ExtensibleSchema([]), 'query{ myalias: test{field1, field2} }', new ArrayObject(), null, ['variable1' => 'v1'])
        ));

        $collector->collect($request, new Response());

        $this->assertEquals($collector->getSchema(), 'myschema');
        $this->assertEquals($collector->getName(), 'graphql');
        $this->assertEquals($collector->getCount(), 1);
        $this->assertTrue($collector->getError());
        $batches = $collector->getBatches();

        $batchError = $batches[0];
        $batchSuccess = $batches[1];

        $this->assertEquals($batchError['count'], 0);
        $this->assertTrue(isset($batchError['error']['message']));

        $this->assertEquals($batchSuccess['count'], 1);
        $this->assertFalse(isset($batchSuccess['error']));
        $this->assertTrue($batchSuccess['variables'] instanceof Data);
        $variables = $batchSuccess['variables']->getValue();
        $this->assertIsArray($variables);
        $this->assertTrue(isset($variables['variable1']));

        $this->assertNotNull($variables);
        $this->assertEquals($batchSuccess['graphql'], [
            'operation' => 'query',
            'operationName' => null,
            'fields' => [
                [
                    'name' => 'test',
                    'alias' => 'myalias',
                ],
            ],
        ]);
    }
}
