<?php

namespace Overblog\GraphQLBundle\Request;

use Symfony\Component\HttpFoundation\Request;

interface ParserInterface
{
    const CONTENT_TYPE_GRAPHQL = 'application/graphql';
    const CONTENT_TYPE_JSON = 'application/json';
    const CONTENT_TYPE_FORM = 'application/x-www-form-urlencoded';
    const CONTENT_TYPE_FORM_DATA = 'multipart/form-data';

    const PARAM_QUERY = 'query';
    const PARAM_VARIABLES = 'variables';
    const PARAM_OPERATION_NAME = 'operationName';

    /**
     * Parses the HTTP request and extracts the GraphQL request parameters.
     *
     * @param Request $request
     *
     * @return array
     */
    public function parse(Request $request);
}
