<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use InvalidArgumentException;
use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use function is_string;
use function sprintf;

final class GlobalVariablesPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $taggedServices = $container->findTaggedServiceIds('overblog_graphql.global_variable', true);
        $globalVariables = ['container' => new Reference('service_container')];
        $expressionLanguageDefinition = $container->findDefinition('overblog_graphql.expression_language');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                if (empty($attributes['alias']) || !is_string($attributes['alias'])) {
                    throw new InvalidArgumentException(
                        sprintf('Service "%s" tagged "overblog_graphql.global_variable" should have a valid "alias" attribute.', $id)
                    );
                }
                $globalVariables[$attributes['alias']] = new Reference($id);

                $isPublic = isset($attributes['public']) ? (bool) $attributes['public'] : true;
                if ($isPublic) {
                    $expressionLanguageDefinition->addMethodCall(
                        'addGlobalName',
                        [
                            sprintf('globalVariables->get(\'%s\')', $attributes['alias']),
                            $attributes['alias'],
                        ]
                    );
                }
            }
        }
        $container->findDefinition(GlobalVariables::class)->setArguments([$globalVariables]);
    }
}
