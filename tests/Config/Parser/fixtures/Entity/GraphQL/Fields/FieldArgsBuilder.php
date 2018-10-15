<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\Entity\GraphQL\Fields;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type(name="Type")
 */
class FieldArgsBuilder
{
    /**
     * @GQL\Field(argsBuilder="MyArgBuilder")
     */
    public $planets;

    /**
     * @GQL\Field(
     *    name="friends",
     *    type="[Character]",
     *    argsBuilder={"MyArgBuilder", {"defaultArg": 1, "option2": "smile"}})
     * )
     */
    public function getFriends($gender, $limit)
    {
        return [];
    }
}
