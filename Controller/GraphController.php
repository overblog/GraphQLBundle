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
        if ($request->query->has('batch')) {
            $data = $this->treatBatchQuery($request);
        } else {
            $data = $this->treatNormalQuery($request);
        }

        return new JsonResponse($data, 200);
    }

    private function treatBatchQuery(Request $request)
    {
        $params = $this->get('overblog_graphql.request_batch_parser')->parse($request);
        $data = [];

        foreach ($params as $i => $entry) {
            $data[$i] = $this->get('overblog_graphql.request_executor')->execute($entry)->toArray();
        }

        return $data;
    }

    private function treatNormalQuery(Request $request)
    {
        $params = $this->get('overblog_graphql.request_parser')->parse($request);
        $data = $this->get('overblog_graphql.request_executor')->execute($params)->toArray();

        return $data;
    }
}
