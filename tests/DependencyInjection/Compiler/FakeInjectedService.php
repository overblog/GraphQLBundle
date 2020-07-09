<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\DependencyInjection\Compiler;

/**
 * Class FakeInjectedService.
 */
class FakeInjectedService
{
    public function doSomething(): bool
    {
        return true;
    }
}
