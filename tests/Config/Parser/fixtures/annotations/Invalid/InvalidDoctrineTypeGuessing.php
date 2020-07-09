<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Invalid;

use Doctrine\ORM\Mapping as ORM;
use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type
 */
class InvalidDoctrineTypeGuessing
{
    /**
     * @ORM\Column(type="invalidType")
     * @GQL\Field
     *
     * @var mixed
     */
    protected $myRelation;
}
