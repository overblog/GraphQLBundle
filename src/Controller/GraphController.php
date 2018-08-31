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
     * @var bool
     */
    private $useApolloBatchingMethod;

    public function __construct(
        GraphQLRequest\ParserInterface $batchParser,
        GraphQLRequest\Executor $requestExecutor,
        GraphQLRequest\ParserInterface $requestParser,
        $shouldHandleCORS,
        $graphQLBatchingMethod
    ) {
        $this->batchParser = $batchParser;
        $this->requestExecutor = $requestExecutor;
        $this->requestParser = $requestParser;
        $this->shouldHandleCORS = $shouldHandleCORS;
        $this->useApolloBatchingMethod = 'apollo' === $graphQLBatchingMethod;
    }

    /**
     * @param Request     $request
     * @param string|null $schemaName
     *
     * @return JsonResponse|Response
     */
    public function endpointAction(Request $request, $schemaName = null)
    {
        return $this->createResponse($request, $schemaName, false);
    }

    /**
     * @param Request     $request
     * @param string|null $schemaName
     *
     * @return JsonResponse|Response
     */
    public function batchEndpointAction(Request $request, $schemaName = null)
    {
        return $this->createResponse($request, $schemaName, true);
    }

    /**
     * @param Request     $request
     * @param string|null $schemaName
     * @param bool        $batched
     *
     * @return JsonResponse|Response
     */
    private function createResponse(Request $request, $schemaName, $batched)
    {
        if ('OPTIONS' === $request->getMethod()) {
            $response = new Response('', 200);
        } else {
            if (!\in_array($request->getMethod(), ['POST', 'GET'])) {
                return new Response('', 405);
            }
            $payload = $this->processQuery($request, $schemaName, $batched);
            $response = new JsonResponse($payload, 200);
        }
        $this->addCORSHeadersIfNeeded($response, $request);

        return $response;
    }

    private function addCORSHeadersIfNeeded(Response $response, Request $request)
    {
        if ($this->shouldHandleCORS && $request->headers->has('Origin')) {
            $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin'), true);
            $response->headers->set('Access-Control-Allow-Credentials', 'true', true);
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization', true);
            $response->headers->set('Access-Control-Allow-Methods', 'OPTIONS, GET, POST', true);
            $response->headers->set('Access-Control-Max-Age', 3600, true);
        }
    }

    /**
     * @param Request     $request
     * @param string|null $schemaName
     * @param bool        $batched
     *
     * @return array
     */
    private function processQuery(Request $request, $schemaName, $batched)
    {
        if ($batched) {
            $payload = $this->processBatchQuery($request, $schemaName);
        } else {
            $payload = $this->processNormalQuery($request, $schemaName);
        }

        return $payload;
    }

    /**
     * @param Request     $request
     * @param string|null $schemaName
     *
     * @return array
     */
    private function processBatchQuery(Request $request, $schemaName = null)
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

    /**
     * @param Request     $request
     * @param string|null $schemaName
     *
     * @return array
     */
    private function processNormalQuery(Request $request, $schemaName = null)
    {
        $params = $this->requestParser->parse($request);

        return $this->requestExecutor->execute($schemaName, $params)->toArray();
    }
}
