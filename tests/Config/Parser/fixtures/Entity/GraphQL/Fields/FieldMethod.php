<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\Entity\GraphQL\Fields;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type(name="Type")
 */
class FieldMethod
{
    /**
     * @GQL\Field(
     *    name="friends",
     *    type="[Character]",
     *    args={
     *      @GQL\FieldArg(name="gender", type="Gender", description="Limit friends of this gender", default="1"),
     *      @GQL\FieldArg(name="limit", type="Int", description="Limit number of friends to retrieve", default=10)
     *    }
     * )
     */
    public function getFriends($gender, $limit)
    {
        return [];
    }
}
