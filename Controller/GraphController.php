<?php

namespace Overblog\GraphQLBundle\Controller;

use Overblog\GraphQLBundle\Request as GraphQLRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GraphController
{
    /**
     * @var GraphQLRequest\BatchParser
     */
    private $batchParser;

    /**
     * @var GraphQLRequest\Executor
     */
    private $requestExecutor;

    /**
     * @var GraphQLRequest\Parser
     */
    private $requestParser;

    /**
     * @var bool
     */
    private $shouldHandleCORS;

    /**
     * @var string
     */
    private $graphQLBatchingMethod;

    public function __construct(
        GraphQLRequest\BatchParser $batchParser,
        GraphQLRequest\Executor $requestExecutor,
        GraphQLRequest\Parser $requestParser,
        $shouldHandleCORS,
        $graphQLBatchingMethod
    ) {
        $this->batchParser = $batchParser;
        $this->requestExecutor = $requestExecutor;
        $this->requestParser = $requestParser;
        $this->shouldHandleCORS = $shouldHandleCORS;
        $this->graphQLBatchingMethod = $graphQLBatchingMethod;
    }

    public function endpointAction(Request $request, $schemaName = null)
    {
        return $this->createResponse($request, $schemaName, false);
    }

    public function batchEndpointAction(Request $request, $schemaName = null)
    {
        return $this->createResponse($request, $schemaName, true);
    }

    private function createResponse(Request $request, $schemaName, $batched)
    {
        if ('OPTIONS' === $request->getMethod()) {
            $response = new Response('', 200);
        } else {
            if (!in_array($request->getMethod(), ['POST', 'GET'])) {
                return new Response('', 405);
            }

            if ($batched) {
                $payload = $this->processBatchQuery($request, $schemaName);
            } else {
                $payload = $this->processNormalQuery($request, $schemaName);
            }

            $response = new JsonResponse($payload, 200);
        }

        if ($this->shouldHandleCORS && $request->headers->has('Origin')) {
            $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin'), true);
            $response->headers->set('Access-Control-Allow-Credentials', 'true', true);
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization', true);
            $response->headers->set('Access-Control-Allow-Methods', 'OPTIONS, GET, POST', true);
            $response->headers->set('Access-Control-Max-Age', 3600, true);
        }

        return $response;
    }

    private function processBatchQuery(Request $request, $schemaName = null)
    {
        $queries = $this->batchParser->parse($request);
        $apolloBatching = 'apollo' === $this->graphQLBatchingMethod;
        $payloads = [];

        foreach ($queries as $query) {
            $payloadResult = $this->requestExecutor->execute(
                [
                    'query' => $query['query'],
                    'variables' => $query['variables'],
                ],
                [],
                $schemaName
            );
            $payloads[] = $apolloBatching ? $payloadResult->toArray() : ['id' => $query['id'], 'payload' => $payloadResult->toArray()];
        }

        return $payloads;
    }

    private function processNormalQuery(Request $request, $schemaName = null)
    {
        $params = $this->requestParser->parse($request);

        return $this->requestExecutor->execute($params, [], $schemaName)->toArray();
    }
}
