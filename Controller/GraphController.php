<?php

namespace Overblog\GraphBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class GraphController extends Controller
{
    public function endpointAction(Request $request)
    {
        try {
            $req = $this->get('graph.request_parser')->parse($request);
            $res = $this->get('graph.request_executor')->execute($req, []);
        } catch (\Exception $e) {
            // Only catch acceptable exceptions.
            if ($this->canHandleException($e)) {
                $code = $this->getStatusCode($e);
                $err = $this->formatError($e);

                return new JsonResponse(['errors' => [$err]], $e->getStatusCode());
            }

            // Don't swallow all exceptions.
            // We need them to bubble up in order to track real failures.
            throw $e;
        }

        $response = new JsonResponse($res->toArray(), 200);

        return $response;
    }

    private function canHandleException(\Exception $e)
    {
        return $e instanceof HttpException
            || $e instanceof AuthenticationException
        ;
    }

    private function getStatusCode(\Exception $e)
    {
        if ($e instanceof HttpException) {
            return $e->getStatusCode();
        }

        if ($e instanceof AuthenticationException) {
            return 401;
        }

        return 500;
    }

    private function formatError(\Exception $e)
    {
        return [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
        ];
    }
}
