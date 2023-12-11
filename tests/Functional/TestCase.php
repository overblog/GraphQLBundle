<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;

if (Kernel::VERSION_ID < 70000) {
    abstract class TestCase extends BaseTestCase
    {
        protected static function getContainer(): ContainerInterface
        {
            /** @phpstan-ignore-next-line */
            return static::$kernel->getContainer();
        }
    }
} else {
    abstract class TestCase extends BaseTestCase
    {
        protected static function getContainer(): Container
        {
            /** @phpstan-ignore-next-line */
            return static::$kernel->getContainer();
        }
    }
}
