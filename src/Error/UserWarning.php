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
    public function isClientSafe()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCategory()
    {
        return 'user';
    }
}
