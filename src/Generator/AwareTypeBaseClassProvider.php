<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator;

use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Generator\TypeBaseClassProvider\TypeBaseClassProviderInterface;

final class AwareTypeBaseClassProvider
{
    /**
     * @var TypeBaseClassProviderInterface[]
     */
    private array $providers = [];

    public function __construct(iterable $providers)
    {
        if ($providers instanceof \Traversable) {
            $providers = iterator_to_array($providers);
        }
        array_walk($providers, fn (TypeBaseClassProviderInterface $x) => $this->addProvider($x));
    }

    public function addProvider(TypeBaseClassProviderInterface $provider): void
    {
        $this->providers[$provider::getType()] = $provider;
    }

    /**
     * @return class-string<Type>
     */
    public function getFQCN(string $type): string
    {
        if (!\array_key_exists($type, $this->providers)) {
            throw new \InvalidArgumentException(sprintf('Not configured type required: "%s"', $type));
        }

        return $this->providers[$type]->getBaseClass();
    }
}
