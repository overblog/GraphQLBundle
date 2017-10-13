<?php

namespace Overblog\GraphQLBundle\Tests\Functional\App\Resolver;

use Overblog\GraphQLBundle\Relay\Node\GlobalId;
use Overblog\GraphQLBundle\Resolver\TypeResolver;

class GlobalResolver
{
    /** @var TypeResolver */
    private $typeResolver;

    private $userData = [
        1 => [
            'id' => 1,
            'name' => 'John Doe',
        ],
        2 => [
            'id' => 2,
            'name' => 'Jane Smith',
        ],
    ];

    private $photoData = [
        1 => [
            'photoId' => 1,
            'width' => 300,
        ],
        2 => [
            'photoId' => 2,
            'width' => 400,
        ],
    ];

    private $postData = [
        1 => [
            'id' => 1,
            'text' => 'lorem',
            'status' => 2,
        ],
        2 => [
            'id' => 2,
            'text' => 'ipsum',
            'status' => 1,
        ],
    ];

    public function __construct(TypeResolver $typeResolver)
    {
        $this->typeResolver = $typeResolver;
    }

    public function idFetcher($globalId)
    {
        list($type, $id) = array_values(GlobalId::fromGlobalId($globalId));

        if ('User' === $type) {
            return $this->userData[$id];
        } elseif ('Photo' === $type) {
            return $this->photoData[$id];
        } else {
            return $this->postData[$id];
        }
    }

    public function typeResolver($value)
    {
        if (isset($value['name'])) {
            return $this->typeResolver->resolve('User');
        } elseif (isset($value['photoId'])) {
            return $this->typeResolver->resolve('Photo');
        } else {
            return $this->typeResolver->resolve('Post');
        }
    }

    public function resolveAllObjects()
    {
        return [
            $this->userData[1], $this->userData[2],
            $this->photoData[1], $this->photoData[2],
            $this->postData[1], $this->postData[2],
        ];
    }
}
