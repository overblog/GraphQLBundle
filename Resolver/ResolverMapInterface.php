<?php

namespace Overblog\GraphQLBundle\Resolver;

interface ResolverMapInterface
{
    // union and interface
    const RESOLVE_TYPE = '__resolveType';
    // object
    const RESOLVE_FIELD = '__resolveField';
    const IS_TYPEOF = '__isTypeOf';
    // custom scalar
    const SCALAR_TYPE = '__scalarType';
    const SERIALIZE = '__serialize';
    const PARSE_VALUE = '__parseValue';
    const PARSE_LITERAL = '__parseLiteral';

    /**
     * Returns the resolver for type category if exists.
     *
     * @param string $typeName
     * @param string $fieldName the field name of the resolver to retrieve
     *
     * @return callable|mixed
     *
     * @throws UnresolvableException if no resolver found
     */
    public function resolve($typeName, $fieldName);

    /**
     * Is the entry mapped?
     *
     * @param string $typeName
     * @param string $fieldName the field name of the resolver to retrieve
     *
     * @return bool
     */
    public function isResolvable($typeName, $fieldName);

    /**
     * Returns the names of the types covered
     * if $typeName equal to null or return the type fields covered
     * by the resolverMap.
     *
     * @param null|string $typeName
     *
     * @return string[]
     */
    public function covered($typeName = null);
}
