<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Parser
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
        $parsedBody = $this->getParsedBody($request);
        $data = $this->getParams($request, $parsedBody);

        return $data;
    }

    /**
     * Gets the body from the request based on Content-Type header.
     *
     * @param Request $request
     *
     * @return array
     */
    private function getParsedBody(Request $request)
    {
        $body = $request->getContent();
        $type = explode(';', $request->headers->get('content-type'))[0];

        switch ($type) {
            // Plain string
            case 'application/graphql':
                $parsedBody = ['query' => $body];
                break;

            // JSON object
            case 'application/json':
                $json = json_decode($body, true);

                if (JSON_ERROR_NONE !== json_last_error()) {
                    throw new BadRequestHttpException(
                        sprintf('POST body sent invalid JSON [%s]', json_last_error_msg())
                    );
                }
                $parsedBody = $json;
                break;

            // URL-encoded query-string
            case 'application/x-www-form-urlencoded':
            case 'multipart/form-data':
                $parsedBody = $request->request->all();
                break;

            default:
                $parsedBody = [];
                break;
        }

        return $parsedBody;
    }

    /**
     * Gets the GraphQL parameters from the request.
     *
     * @param Request $request
     * @param array   $data
     *
     * @return array
     */
    private function getParams(Request $request, array $data = [])
    {
        // Add default request parameters
        $data = $data + [
            'query'         => null,
            'variables'     => null,
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
            'query'         => $query,
            'variables'     => $variables,
            'operationName' => $operationName,
        ];
    }
}
