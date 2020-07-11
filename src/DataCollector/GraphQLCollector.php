<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DataCollector;

use GraphQL\Error\SyntaxError;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\Parser;
use Overblog\GraphQLBundle\Event\ExecutorResultEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Throwable;
use function count;
use function microtime;

class GraphQLCollector extends DataCollector
{
    /**
     * GraphQL Batchs executed.
     */
    protected array $batches = [];

    public function collect(Request $request, Response $response, Throwable $exception = null): void
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
     */
    public function getError(): bool
    {
        return $this->data['error'] ?? false;
    }

    /**
     * Count the number of executed queries.
     */
    public function getCount(): int
    {
        return $this->data['count'] ?? 0;
    }

    /**
     * Return the targeted schema.
     */
    public function getSchema(): string
    {
        return $this->data['schema'] ?? 'default';
    }

    /**
     * Return the list of executed batch.
     */
    public function getBatches(): array
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
     */
    public function onPostExecutor(ExecutorResultEvent $event): void
    {
        $executorArgument = $event->getExecutorArguments();
        $queryString = $executorArgument->getRequestString();
        $operationName = $executorArgument->getOperationName();
        $variables = $executorArgument->getVariableValue();
        $queryTime = microtime(true) - $executorArgument->getStartTime();

        $result = $event->getResult()->toArray();

        $batch = [
            'queryString' => $queryString,
            'queryTime' => $queryTime,
            'variables' => $this->cloneVar($variables),
            'result' => $this->cloneVar($result),
            'count' => 0,
        ];

        try {
            $parsed = Parser::parse($queryString);
            $batch['graphql'] = $this->extractGraphql($parsed, $operationName);
            if (isset($batch['graphql']['fields'])) {
                $batch['count'] += count($batch['graphql']['fields']);
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
     * Extract GraphQL Information from the documentNode.
     */
    protected function extractGraphql(DocumentNode $document, ?string $operationName): array
    {
        $operation = null;
        $fields = [];

        foreach ($document->definitions as $definition) {
            if ($definition instanceof OperationDefinitionNode) {
                $definitionOperation = $definition->name->value ?? null;
                if ($operationName != $definitionOperation) {
                    continue;
                }

                $operation = $definition->operation;
                foreach ($definition->selectionSet->selections as $selection) {
                    if ($selection instanceof FieldNode) {
                        $name = $selection->name->value;
                        $alias = $selection->alias ? $selection->alias->value : null;

                        $fields[] = [
                            'name' => $name,
                            'alias' => $alias,
                        ];
                    }
                }
            }
        }

        return [
            'operation' => $operation,
            'operationName' => $operationName,
            'fields' => $fields,
        ];
    }
}
