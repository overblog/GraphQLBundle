<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use function array_filter;
use function explode;
use function is_string;
use function json_decode;
use function json_encode;
use function json_last_error;
use function sprintf;
use const JSON_ERROR_NONE;

class BatchParser implements ParserInterface
{
    use UploadParserTrait;

    public const PARAM_ID = 'id';

    private const QUERIES_DEFAULT_VALUE = [
        self::PARAM_ID => null,
        self::PARAM_QUERY => null,
        self::PARAM_VARIABLES => null,
    ];

    public function parse(Request $request): array
    {
        // Extracts the GraphQL request parameters
        $queries = $this->getParsedBody($request);

        if (empty($queries)) {
            throw new BadRequestHttpException('Must provide at least one valid query.');
        }

        foreach ($queries as $i => &$query) {
            $query = array_filter($query) + self::QUERIES_DEFAULT_VALUE;

            if (!is_string($query[static::PARAM_QUERY])) {
                throw new BadRequestHttpException(sprintf('%s is not a valid query', json_encode($query[static::PARAM_QUERY])));
            }
        }

        return $queries;
    }

    /**
     * Gets the body from the request.
     */
    private function getParsedBody(Request $request): array
    {
        $contentType = explode(';', (string) $request->headers->get('content-type'), 2)[0];

        // JSON object
        switch ($contentType) {
            case static::CONTENT_TYPE_JSON:
                $parsedBody = json_decode($request->getContent(), true);

                if (JSON_ERROR_NONE !== json_last_error()) {
                    throw new BadRequestHttpException('POST body sent invalid JSON');
                }
                break;

            case static::CONTENT_TYPE_FORM_DATA:
                $parsedBody = $this->handleUploadedFiles($request->request->all(), $request->files->all());
                break;

            default:
                throw new BadRequestHttpException(sprintf(
                    'Batching parser only accepts "%s" or "%s" content-type but got %s.',
                    static::CONTENT_TYPE_JSON,
                    static::CONTENT_TYPE_FORM_DATA,
                    json_encode($contentType)
                ));
        }

        return $parsedBody;
    }
}
