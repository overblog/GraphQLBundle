<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Event;

use GraphQL\Type\Definition\Type;
use Symfony\Contracts\EventDispatcher\Event;

final class TypeLoadedEvent extends Event
{
    private Type $type;
    private ?string $schemaName;

    public function __construct(Type $type, ?string $schemaName)
    {
        $this->type = $type;
        $this->schemaName = $schemaName;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function getSchemaName(): ?string
    {
        return $this->schemaName;
    }
}
