<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\Resolver;

use function sprintf;
use function trigger_error;
use const E_USER_DEPRECATED;

@trigger_error(sprintf('The "%s" interface is deprecated since 0.14 and will be removed in 1.0. Use "%s" instead', ResolverInterface::class, QueryInterface::class), E_USER_DEPRECATED);

/**
 * @deprecated since 0.14 and will be removed in 1.0. Use Overblog\GraphQLBundle\Definition\Resolver\QueryInterface instead.
 * @codeCoverageIgnore
 */
interface ResolverInterface
{
}
