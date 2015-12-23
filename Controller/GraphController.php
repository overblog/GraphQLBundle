<?php

namespace Overblog\GraphBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GraphController extends Controller
{
    public function endpointAction(Request $request)
    {
        $req = $this->get('graph.request_parser')->parse($request);
        $res = $this->get('graph.request_executor')->execute($req, []);

        $response = new JsonResponse($res->toArray(), 200);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
