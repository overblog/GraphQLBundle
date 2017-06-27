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
use GraphQL\Type\LazyResolution;
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

        $config = [
            'query' => $query,
            'mutation' => $mutation,
            'subscription' => $subscription,
            'types' => $this->typeResolver->getSolutions()
        ];

        $descriptorFile = __DIR__.'/../../../../../var/cache/dev/overblog/graph-bundle/schema-descriptor.php';
        if (file_exists($descriptorFile)) {
            $descriptor = include $descriptorFile;

            $config['typeResolution'] = new LazyResolution($descriptor, function($typeName) {
                $classNames = [
                    "Overblog\\GraphQLBundle\\__DEFINITIONS__\\{$typeName}Type",
                    "GraphQL\\Type\\Definition\\{$typeName}Type"
                ];

                foreach ($classNames as $className) {
                    if (class_exists($className)) {
                        return new $className;
                    }
                }

                return null;
            });
        }

        $schema = new Schema($config);

        if (! file_exists($descriptorFile)) {
            file_put_contents(
                $descriptorFile,
                "<?php\n return " . var_export($schema->getDescriptor(), true) . ';'
            );
        }

        return $schema;
    }
}
