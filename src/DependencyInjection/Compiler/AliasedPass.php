<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\MutationInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use function array_filter;
use function call_user_func;
use function is_subclass_of;

final class AliasedPass implements CompilerPassInterface
{
    private const SERVICE_SUBCLASS_TAG_MAPPING = [
        MutationInterface::class => 'overblog_graphql.mutation',
        ResolverInterface::class => 'overblog_graphql.resolver',
        Type::class => TypeTaggedServiceMappingPass::TAG_NAME,
    ];

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $definitions = $this->filterDefinitions($container->getDefinitions());
        foreach ($definitions as $definition) {
            $this->addDefinitionTagsFromAliases($definition);
        }
    }

    /**
     * @param Definition[] $definitions
     *
     * @return Definition[]
     */
    private function filterDefinitions(array $definitions): array
    {
        return array_filter($definitions, function (Definition $definition) {
            foreach (self::SERVICE_SUBCLASS_TAG_MAPPING as $tagName) {
                if ($definition->hasTag($tagName)) {
                    return is_subclass_of($definition->getClass(), AliasedInterface::class);
                }
            }

            return false;
        });
    }

    private function addDefinitionTagsFromAliases(Definition $definition): void
    {
        $aliases = call_user_func([$definition->getClass(), 'getAliases']);
        /** @var string $tagName */
        $tagName = $this->guessTagName($definition);
        $withMethod = TypeTaggedServiceMappingPass::TAG_NAME !== $tagName;

        foreach ($aliases as $key => $alias) {
            $definition->addTag($tagName, $withMethod ? ['alias' => $alias, 'method' => $key] : ['alias' => $alias]);
        }
    }

    private function guessTagName(Definition $definition): ?string
    {
        $tagName = null;
        foreach (self::SERVICE_SUBCLASS_TAG_MAPPING as $refClassName => $tag) {
            if (is_subclass_of($definition->getClass(), $refClassName)) {
                $tagName = $tag;
                break;
            }
        }

        return $tagName;
    }
}
