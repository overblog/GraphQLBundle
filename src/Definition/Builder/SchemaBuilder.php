<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    /** @var bool */
    private $enableValidation;

    public function __construct(ResolverInterface $typeResolver, $enableValidation = false)
    {
        $this->typeResolver = $typeResolver;
        $this->enableValidation = $enableValidation;
    }

    /**
     * @param null|string $queryAlias
     * @param null|string $mutationAlias
     * @param null|string $subscriptionAlias
     *
     * @return Schema
     */
    public function create($queryAlias = null, $mutationAlias = null, $subscriptionAlias = null)
    {
        $this->enableValidation ? Config::enableValidation() : Config::disableValidation();

        $query = $this->typeResolver->resolve($queryAlias);
        $mutation = $this->typeResolver->resolve($mutationAlias);
        $subscription = $this->typeResolver->resolve($subscriptionAlias);

        return new Schema([
            'query' => $query,
            'mutation' => $mutation,
            'subscription' => $subscription,
            'types' => $this->typeResolver->getSolutions(),
        ]);
    }
}
