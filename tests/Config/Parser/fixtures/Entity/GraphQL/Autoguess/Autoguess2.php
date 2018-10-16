<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\Entity\GraphQL\Autoguess;

use Doctrine\ORM\Mapping as ORM;
use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type(name="CustomAutoguessType")
 * @ORM\Entity
 */
class Autoguess2
{
    /**
     * @ORM\Column
     * @GQL\Field
     */
    private $field1;
}
