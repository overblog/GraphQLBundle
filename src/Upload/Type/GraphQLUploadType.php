<?php

namespace Overblog\GraphQLBundle\Upload\Type;

use GraphQL\Error\InvariantViolation;
use GraphQL\Type\Definition\ScalarType;
use Symfony\Component\HttpFoundation\File\File;

class GraphQLUploadType extends ScalarType
{
    /**
     * @param string $name
     */
    public function __construct($name = null)
    {
        parent::__construct([
            'name' => $name,
            'description' => \sprintf(
                'The `%s` scalar type represents a file upload object that resolves an object containing `stream`, `filename`, `mimetype` and `encoding`.',
                $name
            ),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function parseValue($value)
    {
        if (null !== $value && !$value instanceof File) {
            throw new InvariantViolation(\sprintf(
                'Upload should be null or instance of "%s" but %s given.',
                File::class,
                \is_object($value) ? \get_class($value) : \gettype($value)
            ));
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($value)
    {
        throw new InvariantViolation(\sprintf('%s scalar serialization unsupported.', $this->name));
    }

    /**
     * {@inheritdoc}
     */
    public function parseLiteral($valueNode, array $variables = null)
    {
        throw new InvariantViolation(\sprintf('%s scalar literal unsupported.', $this->name));
    }
}
