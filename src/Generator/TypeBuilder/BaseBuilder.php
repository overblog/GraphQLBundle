<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\TypeBuilder;

abstract class BaseBuilder
{
    /**
     * Parses GraphQL type declarations into array, e.g.:
     *
     *  [String]! -> array('list' => true, )
     *
     * @param string $typeDeclaration
     */
    public static function parseType(string $typeDeclaration)
    {

    }
}
