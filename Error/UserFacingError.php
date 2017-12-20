<?php

namespace Overblog\GraphQLBundle\Error;

use GraphQL\Error\ClientAware;

abstract class UserFacingError extends \RuntimeException implements ClientAware
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
