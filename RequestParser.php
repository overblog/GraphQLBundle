<?php

namespace Overblog\GraphBundle;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RequestParser
{
    /**
     * Parses the HTTP request and extracts the GraphQL request parameters.
     *
     * @param Request $request
     *
     * @return array
     */
    public function parse(Request $request)
    {
        // Extracts the GraphQL request parameters
        $body = $this->getBody($request);
        $data = $this->getParams($request, $body);

        return $data;
    }

    /**
     * Gets the body from the request based on Content-Type header.
     *
     * @param Request $request
     *
     * @return array
     */
    private function getBody(Request $request)
    {
        $body = $request->getContent();
        $type = $request->headers->get('content-type');

        // Plain string
        if ('application/graphql' === $type) {
            return ['query' => $body];
        }

        // JSON object
        if ('application/json' === $type) {
            $json = json_decode($body, true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new BadRequestHttpException('POST body sent invalid JSON');
            }

            return $json;
        }

        // URL-encoded query-string
        if ('application/x-www-form-urlencoded' === $type) {
            parse_str($body, $data);

            return $data;
        }

        return [];
    }

    /**
     * Gets the GraphQL parameters from the request.
     *
     * @param Request $request
     * @param array $data
     *
     * @return array
     */
    private function getParams(Request $request, array $data = [])
    {
        // Add default request parameters
        $data = (array)$data + [
                'query' => null,
                'variables' => null,
                'operationName' => null,
            ];

        // Keep a reference to the query-string
        $qs = $request->query;

        // Override request using query-string parameters
        $query = $qs->has('query') ? $qs->get('query') : $data['query'];
        $variables = $qs->has('variables') ? $qs->get('variables') : $data['variables'];
        $operationName = $qs->has('operationName') ? $qs->get('operationName') : $data['operationName'];

        // `query` parameter is mandatory.
        if (empty($query)) {
            throw new BadRequestHttpException('Must provide query parameter');
        }

        // Variables can be defined using a JSON-encoded object.
        // If the parsing fails, an exception will be thrown.
        if (is_string($variables)) {
            $variables = json_decode($variables, true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new BadRequestHttpException('Variables are invalid JSON');
            }
        }

        return [
            'query' => $query,
            'variables' => $variables,
            'operationName' => $operationName,
        ];
    }
}
