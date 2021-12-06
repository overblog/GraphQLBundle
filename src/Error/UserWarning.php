<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Error;

use GraphQL\Error\ClientAware;
use RuntimeException;

class UserWarning extends RuntimeException implements ClientAware
{
    /**
     * {@inheritdoc}
     */
    public function isClientSafe(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCategory(): string
    {
        return 'user';
    }
}
