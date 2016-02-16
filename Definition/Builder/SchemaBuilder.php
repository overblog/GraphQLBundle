<?php

namespace Overblog\GraphQLBundle\Definition\Builder;

use GraphQL\Schema;
use GraphQL\Type\Definition\Config;
use Overblog\GraphQLBundle\Resolver\ResolverInterface;

class SchemaBuilder
{
    /**
     * @var ResolverInterface
     */
    private $typeResolver;

    /** @var Array */
    private $typesMapping;

    /** @var boolean */
    private $enableValidation;

    public function __construct(ResolverInterface $typeResolver, array $typesMapping, $enableValidation = false)
    {
        $this->typeResolver = $typeResolver;
        $this->typesMapping = $typesMapping;
        $this->enableValidation = $enableValidation;
    }

    /**
     * @param null|string $queryAlias
     * @param null|string $mutationAlias
     * @param null $subscriptionAlias
     * @return Schema
     */
    public function create($queryAlias = null, $mutationAlias = null, $subscriptionAlias = null)
    {
        $this->enableValidation ? Config::enableValidation() : Config::disableValidation();
        $this->warmUpTypes();

        $query = $this->typeResolver->resolve($queryAlias);
        $mutation = $this->typeResolver->resolve($mutationAlias);
        $subscription = $this->typeResolver->resolve($subscriptionAlias);

        return new Schema($query, $mutation, $subscription);
    }

    private function warmUpTypes()
    {
        foreach ($this->typesMapping as $alias => $serviceId) {
            $this->typeResolver->resolve($alias);
        }
    }
}
