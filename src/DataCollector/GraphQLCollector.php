<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DataCollector;

use GraphQL\Error\SyntaxError;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\Parser;
use Overblog\GraphQLBundle\Event\ExecutorResultEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class GraphQLCollector extends DataCollector
{
    /**
     * GraphQL Batchs executed.
     */
    protected $batches = [];

    public function collect(Request $request, Response $response, \Throwable $exception = null): void
    {
        $error = false;
        $count = 0;
        foreach ($this->batches as $batch) {
            if (isset($batch['error'])) {
                $error = true;
            }
            $count += $batch['count'];
        }

        $this->data = [
            'schema' => $request->attributes->get('_route_params')['schemaName'] ?? 'default',
            'batches' => $this->batches,
            'count' => $count,
            'error' => $error,
        ];
    }

    /**
     * Check if we have an error.
     *
     * @return boolean
     */
    public function getError()
    {
        return $this->data['error'] ?? false;
    }

    /**
     * Count the number of executed queries.
     *
     * @return int
     */
    public function getCount()
    {
        return $this->data['count'] ?? 0;
    }

    /**
     * Return the targeted schema.
     *
     * @return string
     */
    public function getSchema()
    {
        return $this->data['schema'] ?? 'default';
    }

    /**
     * Return the list of executed batch.
     *
     * @return array
     */
    public function getBatches()
    {
        return $this->data['batches'] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function reset(): void
    {
        $this->data = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'graphql';
    }

    /**
     * Hook into the GraphQL events to populate the collector.
     *
     * @param ExecutorResultEvent $event
     */
    public function onPostExecutor(ExecutorResultEvent $event): void
    {
        $executorArgument = $event->getExecutorArguments();
        $queryString = $executorArgument->getRequestString();
        $variables = $executorArgument->getVariableValue();

        $result = $event->getResult()->toArray();

        $batch = [
            'queryString' => $queryString,
            'variables' => $this->cloneVar($variables),
            'result' => $this->cloneVar($result),
            'count' => 0,
        ];

        try {
            $parsed = Parser::parse($queryString);
            $batch['graphql'] = $this->extractGraphql($parsed);
            if (isset($batch['graphql']['fields'])) {
                $batch['count'] += \count($batch['graphql']['fields']);
            }
            $error = $result['errors'][0] ?? false;
            if ($error) {
                $batch['error'] = [
                    'message' => $error['message'],
                    'location' => $error['locations'][0] ?? false,
                ];
            }
        } catch (SyntaxError $error) {
            $location = $error->getLocations()[0] ?? false;
            $batch['error'] = ['message' => $error->getMessage(), 'location' => $location];
        }

        $this->batches[] = $batch;
    }

    /**
     * Extract GraphQL Information from the documentNode².
     *
     * @param DocumentNode $document
     * @param array        $result
     * @param array        $variables
     *
     * @return array
     */
    protected function extractGraphql(DocumentNode $document)
    {
        $operation = null;
        $fields = [];

        foreach ($document->definitions as $definition) {
            if ($definition instanceof OperationDefinitionNode) {
                $operation = $definition->operation;
                foreach ($definition->selectionSet->selections as $selection) {
                    $name = $selection->name->value;
                    $alias = $selection->alias ? $selection->alias->value : null;

                    $fields[] = [
                        'name' => $name,
                        'alias' => $alias,
                    ];
                }
            }
        }

        return [
            'operation' => $operation,
            'fields' => $fields,
        ];
    }
}
