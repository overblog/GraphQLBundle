<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\Config;

final class Callback extends AbstractConfig
{
    public ?string $function = null;
    public ?string $expression = null;
}
