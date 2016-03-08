<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Functional\app\Resolver;

use Overblog\GraphQLBundle\Resolver\TypeResolver;

class NodeResolver
{
    /** @var TypeResolver  */
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
            'id' => 3,
            'width' => 300,
        ],
        '4' => [
            'id' => 4,
            'width' => 400,
        ],
    ];

    public function __construct(TypeResolver $typeResolver)
    {
        $this->typeResolver = $typeResolver;
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
