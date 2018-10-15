<?php

declare (strict_types = 1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\Entity\GraphQL\Inherits;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type
 */
class ChildClass extends ParentClass
{
    /**
     * @GQL\Field(fieldBuilder={"NoteFieldBuilder", {"option": "value"}})
     */
    public $notes;
}

