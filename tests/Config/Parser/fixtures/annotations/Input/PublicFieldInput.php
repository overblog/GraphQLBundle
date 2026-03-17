<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Input;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Input
 */
#[GQL\Input]
final class PublicFieldInput
{
    /**
     * @GQL\Field(type="String!")
     *
     * @GQL\IsPublic("isAuthenticated()")
     */
    #[GQL\Field(type: 'String!')]
    #[GQL\IsPublic('isAuthenticated()')]
    public string $restrictedField;

    /**
     * @GQL\Field(type="Int")
     */
    #[GQL\Field(type: 'Int')]
    public ?int $publicField;
}
