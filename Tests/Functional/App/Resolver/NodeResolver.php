<?php

namespace Overblog\GraphQLBundle\Tests\Functional\App\Resolver;

use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Resolver\TypeResolver;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class NodeResolver implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @var TypeResolver */
    private $typeResolver;

    private $userData = [
        '1' => [
            'id' => 1,
            'name' => 'John Doe',
        ],
        '2' => [
            'id' => 2,
            'name' => 'Jane Smith',
        ],
    ];

    private $photoData = [
        '3' => [
            'photoID' => 3,
            'photoWidth' => 300,
        ],
        '4' => [
            'photoID' => 4,
            'photoWidth' => 400,
        ],
    ];

    public function __construct(TypeResolver $typeResolver)
    {
        $this->typeResolver = $typeResolver;
    }

    public function resolvePhotoField($value, ResolveInfo $info)
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

    public function idFetcher($id)
    {
        if (isset($this->userData[$id])) {
            return $this->userData[$id];
        } elseif (isset($this->photoData[$id])) {
            return $this->photoData[$id];
        }

        return;
    }

    public function typeResolver($value)
    {
        if (isset($value['name'])) {
            return $this->typeResolver->resolve('User');
        } else {
            return $this->typeResolver->resolve('Photo');
        }
    }
}
