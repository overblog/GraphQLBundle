<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Invalid;

use Doctrine\ORM\Mapping as ORM;
use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type
 */
#[GQL\Type]
final class InvalidDoctrineRelationGuessing
{
    /**
     * @ORM\OneToOne(targetEntity="MissingType")
     *
     * @GQL\Field
     */
    #[GQL\Field]
    public object $myRelation;
}
