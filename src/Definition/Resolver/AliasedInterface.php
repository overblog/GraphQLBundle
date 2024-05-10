<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\Resolver;

interface AliasedInterface
{
    /**
     * Returns methods aliases.
     *
     * For instance:
     * array('myMethod' => 'myAlias')
     *
     * @return list<string>
     */
    public static function getAliases(): array;
}
