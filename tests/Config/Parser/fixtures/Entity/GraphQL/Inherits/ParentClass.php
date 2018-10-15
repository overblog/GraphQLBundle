<?php

declare (strict_types = 1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\Entity\GraphQL\Inherits;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type
 */
class ParentClass
{
    /**
     * @GQL\Field(fieldBuilder="GenericIdBuilder")
     */
    public $id;

}

