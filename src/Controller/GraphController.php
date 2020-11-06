<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Controller;

use Overblog\GraphQLBundle\Request\BatchParser;
use Overblog\GraphQLBundle\Request\Executor;
use Overblog\GraphQLBundle\Request\Parser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function in_array;

class GraphController
{
    private BatchParser $batchParser;
    private Executor $requestExecutor;
    private Parser $requestParser;
    private bool $shouldHandleCORS;
    private bool $useApolloBatchingMethod;

    public function __construct(
        BatchParser $batchParser,
        Executor $requestExecutor,
        Parser $requestParser,
        bool $shouldHandleCORS,
        string $graphQLBatchingMethod
    ) {
        $this->batchParser = $batchParser;
        $this->requestExecutor = $requestExecutor;
        $this->requestParser = $requestParser;
        $this->shouldHandleCORS = $shouldHandleCORS;
        $this->useApolloBatchingMethod = 'apollo' === $graphQLBatchingMethod;
    }

    /**
     * @return JsonResponse|Response
     */
    public function endpointAction(Request $request, string $schemaName = null)
    {
        return $this->createResponse($request, $schemaName, false);
    }

    /**
     * @return JsonResponse|Response
     */
    public function batchEndpointAction(Request $request, string $schemaName = null)
    {
        return $this->createResponse($request, $schemaName, true);
    }

    /**
     * @return JsonResponse|Response
     */
    private function createResponse(Request $request, ?string $schemaName, bool $batched)
    {
        if ('OPTIONS' === $request->getMethod()) {
            $response = new JsonResponse([], 200);
        } else {
            if (!in_array($request->getMethod(), ['POST', 'GET'])) {
                return new JsonResponse('', 405);
            }
            $payload = $this->processQuery($request, $schemaName, $batched);
            $response = new JsonResponse($payload, 200);
        }
        $this->addCORSHeadersIfNeeded($response, $request);

        return $response;
    }

    private function addCORSHeadersIfNeeded(Response $response, Request $request): void
    {
        if ($this->shouldHandleCORS && $request->headers->has('Origin')) {
            $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin'), true);
            $response->headers->set('Access-Control-Allow-Credentials', 'true', true);
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization', true);
            $response->headers->set('Access-Control-Allow-Methods', 'OPTIONS, GET, POST', true);
            $response->headers->set('Access-Control-Max-Age', '3600', true);
        }
    }

    private function processQuery(Request $request, ?string $schemaName, bool $batched): array
    {
        if ($batched) {
            $payload = $this->processBatchQuery($request, $schemaName);
        } else {
            $payload = $this->processNormalQuery($request, $schemaName);
        }

        return $payload;
    }

    private function processBatchQuery(Request $request, string $schemaName = null): array
    {
        $queries = $this->batchParser->parse($request);
        $payloads = [];

        foreach ($queries as $query) {
            $payload = $this->requestExecutor
                ->execute($schemaName, ['query' => $query['query'], 'variables' => $query['variables']])
                ->toArray();
            if (!$this->useApolloBatchingMethod) {
                $payload = ['id' => $query['id'], 'payload' => $payload];
            }
            $payloads[] = $payload;
        }

        return $payloads;
    }

    private function processNormalQuery(Request $request, string $schemaName = null): array
    {
        $params = $this->requestParser->parse($request);

        return $this->requestExecutor->execute($schemaName, $params)->toArray();
    }
}
