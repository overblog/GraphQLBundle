<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Service;

final class PrivateService
{
    public function hasAccess(): bool
    {
        return true;
    }
}
