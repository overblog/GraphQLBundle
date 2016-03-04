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
        $req = $this->get('overblog_graphql.request_parser')->parse($request);
        $res = $this->get('overblog_graphql.request_executor')->execute($req, []);

        return new JsonResponse($res->toArray(), 200);
    }
}
