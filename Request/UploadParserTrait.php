<?php

namespace Overblog\GraphQLBundle\Request;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;

trait UploadParserTrait
{
    /**
     * @param array $operations
     * @param array $map
     * @param array $files
     *
     * @return array
     */
    protected function mappingUploadFiles(array $operations, array $map, array $files)
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

    protected function locationToPropertyAccessPath($location)
    {
        return array_reduce(
            explode('.', $location),
            function ($carry, $item) {
                return sprintf('%s[%s]', $carry, $item);
            }
        );
    }

    protected function isUploadPayload(array $payload)
    {
        return isset($payload['operations']) && isset($payload['map']) && is_array($payload['operations']) && is_array($payload['map']);
    }

    protected function treatUploadFiles(array $parsedBody, array $files)
    {
        $payload = $this->normalized($parsedBody);
        if ($this->isUploadPayload($payload)) {
            return $this->mappingUploadFiles($payload['operations'], $payload['map'], $files);
        } else {
            return $parsedBody;
        }
    }

    protected function normalized(array $parsedBody)
    {
        foreach (['operations', 'map'] as $key) {
            if (isset($parsedBody[$key]) && is_string($parsedBody[$key])) {
                $parsedBody[$key] = json_decode($parsedBody[$key], true);
            }
        }

        return $parsedBody;
    }
}
