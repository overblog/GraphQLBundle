<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Input;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Input
 */
#[GQL\Input]
final class MateriaInput
{
    /**
     * @GQL\InputField(type="String!")
     */
    #[GQL\InputField(type: 'String!')]
    public string $name = 'default name';

    /**
     * @GQL\InputField(type="Int!", defaultValue=100)
     */
    #[GQL\InputField(type: 'Int!', defaultValue: 100)]
    public int $ap;

    /**
     * @GQL\Field
     */
    #[GQL\Field]
    public string $description = 'A description by default';

    /**
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    public ?int $diameter;

    // @phpstan-ignore-next-line
    public $dummy;

    /**
     * @GQL\Field(type="[String]!")
     */
    #[GQL\Field(type: '[String]!')]
    public array $colors = ['red', 'green', 'blue'];

    /**
     * @GQL\InputField(type="[String]", defaultValue={"slow", "enrage", "boost"})
     */
    #[GQL\InputField(type: '[String]', defaultValue: ['slow', 'enrage', 'boost'])]
    public ?array $effects = null;
}
