<?php

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class AliasedPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
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
    private function filterDefinitions($definitions)
    {
        return array_filter($definitions, function (Definition $definition) {
            foreach (AutoMappingPass::SERVICE_SUBCLASS_TAG_MAPPING as $tagName) {
                if ($definition->hasTag($tagName)) {
                    return is_subclass_of($definition->getClass(), AliasedInterface::class);
                }
            }

            return false;
        });
    }

    /**
     * @param Definition $definition
     */
    private function addDefinitionTagsFromAliases(Definition $definition)
    {
        $aliases = call_user_func([$definition->getClass(), 'getAliases']);
        $tagName = $this->guessTagName($definition);
        $withMethod = TypeTaggedServiceMappingPass::TAG_NAME !== $tagName;

        foreach ($aliases as $key => $alias) {
            $definition->addTag($tagName, $withMethod ? ['alias' => $alias, 'method' => $key] : ['alias' => $alias]);
        }
    }

    private function guessTagName(Definition $definition)
    {
        $tagName = null;
        foreach (AutoMappingPass::SERVICE_SUBCLASS_TAG_MAPPING as $refClassName => $tag) {
            if (is_subclass_of($definition->getClass(), $refClassName)) {
                $tagName = $tag;
                break;
            }
        }

        return $tagName;
    }
}
