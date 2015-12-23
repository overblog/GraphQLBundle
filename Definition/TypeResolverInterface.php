<?php

namespace Overblog\GraphBundle\Definition;

interface TypeResolverInterface
{
    /**
     * Resolves the specified type expression.
     *
     *    name          = alpha, [alnum]
     *        type          = named_type | list_type | non_null_type
     *        named_type    = name
     *        list_type     = "[", type, "]"
     *        non_null_type = { named_type | list_type }, "!"
     *
     * @param string $expr
     *
     * @return \GraphQL\Type\Definition\Type
     */
    public function resolveType($expr);
}
