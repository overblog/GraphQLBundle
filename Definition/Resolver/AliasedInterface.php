<?php

namespace Overblog\GraphQLBundle\Definition\Resolver;

interface AliasedInterface
{
    /**
     * Returns methods aliases.
     *
     * For instance:
     * array('myMethod' => 'myAlias')
     *
     * @return array
     */
    public static function getAliases();
}
