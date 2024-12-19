<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Resolver;

interface ResolverMapInterface
{
    // union and interface
    public const RESOLVE_TYPE = '%%resolveType';
    // object
    public const RESOLVE_FIELD = '%%resolveField';
    public const IS_TYPEOF = '%%isTypeOf';
    // custom scalar
    public const SCALAR_TYPE = '%%scalarType';
    public const SERIALIZE = '%%serialize';
    public const PARSE_VALUE = '%%parseValue';
    public const PARSE_LITERAL = '%%parseLiteral';

    /**
     * Returns the resolver for type category if exists.
     *
     * @param string $fieldName the field name of the resolver to retrieve
     *
     * @return callable|mixed
     *
     * @throws UnresolvableException if no resolver found
     */
    public function resolve(string $typeName, string $fieldName);

    /**
     * Is the entry mapped?
     *
     * @param string $fieldName the field name of the resolver to retrieve
     */
    public function isResolvable(string $typeName, string $fieldName): bool;

    /**
     * Returns the names of the types covered
     * if $typeName equal to null or return the type fields covered
     * by the resolverMap.
     *
     * @return array
     */
    public function covered(?string $typeName = null);
}
