<?php

namespace Overblog\GraphQLBundle\Error;

use GraphQL\Error\ClientAware;

/**
 * Class UserWarning.
 */
class UserWarning extends \RuntimeException implements ClientAware
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
