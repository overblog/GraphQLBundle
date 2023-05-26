<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Union;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Union(name="ResultSearch", types={"Hero", "Droid", "Sith"}, resolveType="value.getType()")
 *
 * @GQL\Description("A search result")
 */
#[GQL\Union('ResultSearch', types: ['Hero', 'Droid', 'Sith'], resolveType: 'value.getType()')]
#[GQL\Description('A search result')]
final class SearchResult
{
}
