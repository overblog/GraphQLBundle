<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\Config;

final class Callback extends AbstractConfig
{
    public ?string $method = null;
    public ?string $expression = null;
    public ?string $id = null;
}
