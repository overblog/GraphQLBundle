<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition;

use ArrayAccess;
use Countable;

interface ArgumentInterface extends ArrayAccess, Countable
{
    /**
     * @return array the old array
     */
    public function exchangeArray(array $array): array;

    public function getArrayCopy(): array;
}
