<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Type;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\TypeInterface(resolveType="@=query('character_type', [value])")
 *
 * @GQL\Description("The character interface")
 */
#[GQL\TypeInterface(resolveType: "@=query('character_type', [value])")]
#[GQL\Description('The character interface')]
abstract class Character
{
    /**
     * @GQL\Field(type="String!")
     *
     * @GQL\Description("The name of the character")
     */
    #[GQL\Field(type: 'String!')]
    #[GQL\Description('The name of the character')]
    protected string $name;

    /**
     * @GQL\Field(type="[Character]", resolve="@=query('App\MyResolver::getFriends')")
     *
     * @GQL\Description("The friends of the character")
     */
    #[GQL\Field(type: '[Character]', resolve: "@=query('App\MyResolver::getFriends')")]
    #[GQL\Description('The friends of the character')]
    protected array $friends;
}
