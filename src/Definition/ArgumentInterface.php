<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition;

interface ArgumentInterface extends \ArrayAccess, \Countable
{
    /**
     * @param $array
     *
     * @return array the old array
     */
    public function exchangeArray($array): array;

    public function getArrayCopy(): array;
}
