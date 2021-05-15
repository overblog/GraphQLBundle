<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\Config;

final class Validation extends AbstractConfig
{
    public ?array $constraints = null;
    public ?string $link = null;
    public ?array $cascade = null;
}
