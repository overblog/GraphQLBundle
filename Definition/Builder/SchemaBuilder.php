<?php

namespace Overblog\GraphBundle\Definition\Builder;

use GraphQL\Schema;
use Overblog\GraphBundle\Resolver\ResolverInterface;

class SchemaBuilder
{
    /**
     * @var ResolverInterface
     */
    private $typeResolver;

    public function __construct(ResolverInterface $typeResolver)
    {
        $this->typeResolver = $typeResolver;
    }

    /**
     * @param null|string $queryAlias
     * @param null|string $mutationAlias
     * @return Schema
     *
     */
    public function create($queryAlias = null, $mutationAlias = null)
    {
        $query = $this->typeResolver->resolve($queryAlias);
        $mutation = $this->typeResolver->resolve($mutationAlias);

        return new Schema($query, $mutation);
    }
}
