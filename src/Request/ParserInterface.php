<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Request;

use Symfony\Component\HttpFoundation\Request;

interface ParserInterface
{
    public const CONTENT_TYPE_GRAPHQL = 'application/graphql';
    public const CONTENT_TYPE_JSON = 'application/json';
    public const CONTENT_TYPE_FORM = 'application/x-www-form-urlencoded';
    public const CONTENT_TYPE_FORM_DATA = 'multipart/form-data';

    public const PARAM_QUERY = 'query';
    public const PARAM_VARIABLES = 'variables';
    public const PARAM_OPERATION_NAME = 'operationName';

    /**
     * Parses the HTTP request and extracts the GraphQL request parameters.
     */
    public function parse(Request $request): array;
}
