<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\Entity\GraphQL\Union;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Union(types={"Dog", "Cat", "Bird", "Snake"})
 * @GQL\Description("All the pets")
 */
class Pet
{
}
