<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Validator\Mapping;

use Overblog\GraphQLBundle\Validator\ValidationNode;
use Symfony\Component\Validator\Exception\NoSuchMetadataException;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;

/**
 * @author Timur Murtukov <murtukov@gmail.com>
 */
class MetadataFactory implements MetadataFactoryInterface
{
    private $metadataPool;

    public function __construct()
    {
        $this->metadataPool = [];
    }

    public function getMetadataFor($object)
    {
        if ($object instanceof ValidationNode) {
            return $this->metadataPool[$object->getName()];
        }

        throw new NoSuchMetadataException();
    }

    public function hasMetadataFor($object)
    {
        if ($object instanceof ValidationNode) {
            return isset($this->metadataPool[$object->getName()]);
        }

        return false;
    }

    public function addMetadata(ObjectMetadata $metadata): void
    {
        $this->metadataPool[$metadata->getClassName()] = $metadata;
    }
}
