<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Request;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use function array_keys;
use function array_reduce;
use function array_search;
use function explode;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function sprintf;

trait UploadParserTrait
{
    private function handleUploadedFiles(array $parameters, array $files): array
    {
        $payload = $this->normalized($parameters);
        if ($this->isUploadPayload($payload)) {
            return $this->bindUploadedFiles($payload['operations'], $payload['map'], $files);
        } else {
            return $parameters;
        }
    }

    private function bindUploadedFiles(array $operations, array $map, array $files): array
    {
        $accessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->getPropertyAccessor();

        foreach ($map as $fileName => $locations) {
            foreach ($locations as $location) {
                $fileKey = sprintf('[%s]', $fileName);
                if (!$accessor->isReadable($files, $fileKey)) {
                    throw new BadRequestHttpException(sprintf('File %s is missing in the request.', json_encode($fileName)));
                }
                $file = $accessor->getValue($files, $fileKey);
                $locationKey = $this->locationToPropertyAccessPath($location);
                if (!$accessor->isReadable($operations, $locationKey)) {
                    throw new BadRequestHttpException(sprintf('Map entry %s could not be localized in operations.', json_encode($location)));
                }
                $accessor->setValue($operations, $locationKey, $file);
            }
        }

        return $operations;
    }

    private function isUploadPayload(array $payload): bool
    {
        if (isset($payload['operations']) && isset($payload['map']) && is_array($payload['operations']) && is_array($payload['map'])) {
            $payloadKeys = array_keys($payload);
            // the specs says that operations must be place before map
            $operationsPosition = array_search('operations', $payloadKeys);
            $mapPosition = array_search('map', $payloadKeys);

            return $operationsPosition < $mapPosition;
        } else {
            return false;
        }
    }

    /**
     * @return mixed|null
     */
    private function locationToPropertyAccessPath(string $location)
    {
        return array_reduce(
            explode('.', $location),
            function ($carry, $item) {
                return sprintf('%s[%s]', $carry, $item);
            }
        );
    }

    private function normalized(array $parsedBody): array
    {
        foreach (['operations', 'map'] as $key) {
            if (isset($parsedBody[$key]) && is_string($parsedBody[$key])) {
                $parsedBody[$key] = json_decode($parsedBody[$key], true);
            }
        }

        return $parsedBody;
    }
}
