<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Union;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Union(types={"Hero", "Droid", "Sith"})
 * @GQL\Description("A search result")
 */
class SearchResult
{
}
