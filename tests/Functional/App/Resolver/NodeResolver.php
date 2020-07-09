<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Resolver;

use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Resolver\TypeResolver;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class NodeResolver implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private array $userData = [
        '1' => [
            'id' => 1,
            'name' => 'John Doe',
        ],
        '2' => [
            'id' => 2,
            'name' => 'Jane Smith',
        ],
    ];

    private array $photoData = [
        '3' => [
            'photoID' => 3,
            'photoWidth' => 300,
        ],
        '4' => [
            'photoID' => 4,
            'photoWidth' => 400,
        ],
    ];

    public function resolvePhotoField(array $value, ResolveInfo $info): ?int
    {
        switch ($info->fieldName) {
            case 'id':
                return $value['photoID'];
            case 'width':
                return $value['photoWidth'];
            default:
                return null;
        }
    }

    public function idFetcher(string $id): ?array
    {
        if (isset($this->userData[$id])) {
            return $this->userData[$id];
        } elseif (isset($this->photoData[$id])) {
            return $this->photoData[$id];
        }

        return null;
    }

    public function typeResolver(array $value): string
    {
        if (isset($value['name'])) {
            return 'User';
        } else {
            return 'Photo';
        }
    }
}
