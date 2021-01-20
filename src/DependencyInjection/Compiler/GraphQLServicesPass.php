<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use InvalidArgumentException;
use Overblog\GraphQLBundle\Definition\GraphQLServices;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use function is_string;
use function sprintf;

final class GraphQLServicesPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $taggedServices = $container->findTaggedServiceIds('overblog_graphql.service', true);

        // TODO: remove following if-block in 1.0
        if (count($deprecatedTaggedServices = $container->findTaggedServiceIds('overblog_graphql.global_variable', true)) > 0) {
            @trigger_error(
                "The tag 'overblog_graphql.global_variable' is deprecated since 0.14 and will be removed in 1.0. Use 'overblog_graphql.service' instead. For more info visit: https://github.com/overblog/GraphQLBundle/issues/775",
                E_USER_DEPRECATED
            );

            $taggedServices = array_merge($taggedServices, $deprecatedTaggedServices);
        }

        $serviceContainer = ['container' => new Reference('service_container')];
        $expressionLanguageDefinition = $container->findDefinition('overblog_graphql.expression_language');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                if (empty($attributes['alias']) || !is_string($attributes['alias'])) {
                    throw new InvalidArgumentException(
                        sprintf('Service "%s" tagged "overblog_graphql.service" should have a valid "alias" attribute.', $id)
                    );
                }
                $serviceContainer[$attributes['alias']] = new Reference($id);

                $isPublic = isset($attributes['public']) ? (bool) $attributes['public'] : true;
                if ($isPublic) {
                    $expressionLanguageDefinition->addMethodCall(
                        'addGlobalName',
                        [
                            sprintf(TypeGenerator::GRAPHQL_SERVICES.'->get(\'%s\')', $attributes['alias']),
                            $attributes['alias'],
                        ]
                    );
                }
            }
        }
        $container->findDefinition(GraphQLServices::class)->addArgument($serviceContainer);
    }
}
