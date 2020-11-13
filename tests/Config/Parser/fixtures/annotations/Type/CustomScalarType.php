<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Type;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type
 */
class CustomScalarType
{
    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @GQL\Field
     * @phpstan-ignore-next-line
     */
    protected $doctrineDatetime;

    /**
     * @GQL\Field
     */
    protected DateTime $phpDatetime;
}
