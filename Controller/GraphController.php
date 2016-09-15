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

class GraphController extends Controller
{
    public function endpointAction(Request $request)
    {
        $payload = $this->processNormalQuery($request);

        return new JsonResponse($payload, 200);
    }

    public function batchEndpointAction(Request $request)
    {
        $payloads = $this->processBatchQuery($request);

        return new JsonResponse($payloads, 200);
    }

    private function processBatchQuery(Request $request)
    {
        $queries = $this->get('overblog_graphql.request_batch_parser')->parse($request);
        $payloads = [];

        foreach ($queries as $query) {
            $payloadResult = $this->get('overblog_graphql.request_executor')->execute([
                'query' => $query['query'],
                'variables' => $query['variables'],
            ]);
            $payloads[] = ['id' => $query['id'], 'payload' => $payloadResult->toArray()];
        }

        return $payloads;
    }

    private function processNormalQuery(Request $request)
    {
        $params = $this->get('overblog_graphql.request_parser')->parse($request);
        $data = $this->get('overblog_graphql.request_executor')->execute($params)->toArray();

        return $data;
    }
}
