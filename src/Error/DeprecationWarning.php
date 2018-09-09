<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Error;

use GraphQL\Error\ClientAware;

/**
 * Class UserWarning.
 */
class DeprecationWarning extends \RuntimeException implements ClientAware
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
