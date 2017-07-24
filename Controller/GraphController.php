<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GraphController extends Controller
{
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

        if ($this->container->getParameter('overblog_graphql.handle_cors') && $request->headers->has('Origin')) {
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
        $queries = $this->get('overblog_graphql.request_batch_parser')->parse($request);
        $apolloBatching = 'apollo' === $this->getParameter('overblog_graphql.batching_method');
        $payloads = [];

        foreach ($queries as $query) {
            $payloadResult = $this->get('overblog_graphql.request_executor')->execute(
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
        $params = $this->get('overblog_graphql.request_parser')->parse($request);
        $data = $this->get('overblog_graphql.request_executor')->execute($params, [], $schemaName)->toArray();

        return $data;
    }
}
