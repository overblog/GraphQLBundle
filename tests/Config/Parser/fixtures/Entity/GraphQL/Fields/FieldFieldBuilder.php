<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\Entity\GraphQL\Fields;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type(name="Type")
 */
class FieldFieldBuilder
{
    /**
     * @GQL\Field(fieldBuilder="GenericIdBuilder")
     */
    public $id;

    /**
     * @GQL\Field(fieldBuilder={"NoteFieldBuilder", {"option": "value"}})
     */
    public $notes;
}
