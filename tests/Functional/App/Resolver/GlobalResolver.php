<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Resolver;

use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Relay\Node\GlobalId;
use Overblog\GraphQLBundle\Resolver\TypeResolver;
use function array_values;

class GlobalResolver
{
    private TypeResolver $typeResolver;

    private array $userData = [
        1 => [
            'id' => 1,
            'name' => 'John Doe',
        ],
        2 => [
            'id' => 2,
            'name' => 'Jane Smith',
        ],
    ];

    private array $photoData = [
        1 => [
            'photoId' => 1,
            'width' => 300,
        ],
        2 => [
            'photoId' => 2,
            'width' => 400,
        ],
    ];

    private array $postData = [
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

    public function idFetcher(string $globalId): array
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

    public function typeResolver(array $value): ?Type
    {
        if (isset($value['name'])) {
            return $this->typeResolver->resolve('User');
        } elseif (isset($value['photoId'])) {
            return $this->typeResolver->resolve('Photo');
        } else {
            return $this->typeResolver->resolve('Post');
        }
    }

    public function resolveAllObjects(): array
    {
        return [
            $this->userData[1], $this->userData[2],
            $this->photoData[1], $this->photoData[2],
            $this->postData[1], $this->postData[2],
        ];
    }
}
