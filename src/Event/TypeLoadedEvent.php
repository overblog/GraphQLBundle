<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Event;

use GraphQL\Type\Definition\Type;
use Symfony\Component\EventDispatcher\Event;

final class TypeLoadedEvent extends Event
{
    /** @var Type */
    private $type;

    public function __construct(Type $type)
    {
        $this->type = $type;
    }

    public function getType(): Type
    {
        return $this->type;
    }
}
