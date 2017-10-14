<?php

namespace Overblog\GraphQLBundle\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Parser implements ParserInterface
{
    /**
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
            case static::CONTENT_TYPE_GRAPHQL:
                $parsedBody = [static::PARAM_QUERY => $body];
                break;

            // JSON object
            case static::CONTENT_TYPE_JSON:
                if (empty($body)) {
                    throw new BadRequestHttpException('The request content body must not be empty when using json content type request.');
                }

                $parsedBody = json_decode($body, true);

                if (JSON_ERROR_NONE !== json_last_error()) {
                    throw new BadRequestHttpException('POST body sent invalid JSON');
                }
                break;

            // URL-encoded query-string
            case static::CONTENT_TYPE_FORM:
            case static::CONTENT_TYPE_FORM_DATA:
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
        $data = array_filter($data) + [
                static::PARAM_QUERY => null,
                static::PARAM_VARIABLES => null,
                static::PARAM_OPERATION_NAME => null,
            ];

        // Keep a reference to the query-string
        $qs = $request->query;

        // Override request using query-string parameters
        $query = $qs->has(static::PARAM_QUERY) ? $qs->get(static::PARAM_QUERY) : $data[static::PARAM_QUERY];
        $variables = $qs->has(static::PARAM_VARIABLES) ? $qs->get(static::PARAM_VARIABLES) : $data[static::PARAM_VARIABLES];
        $operationName = $qs->has(static::PARAM_OPERATION_NAME) ? $qs->get(static::PARAM_OPERATION_NAME) : $data[static::PARAM_OPERATION_NAME];

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
            static::PARAM_QUERY => $query,
            static::PARAM_VARIABLES => $variables,
            static::PARAM_OPERATION_NAME => $operationName,
        ];
    }
}
