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

        $locateableServices = [];
        $expressionLanguageDefinition = $container->findDefinition('overblog_graphql.expression_language');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                if (empty($attributes['alias']) || !is_string($attributes['alias'])) {
                    throw new InvalidArgumentException(
                        sprintf('Service "%s" tagged "overblog_graphql.service" should have a valid "alias" attribute.', $id)
                    );
                }
                $locateableServices[$attributes['alias']] = new Reference($id);

                $isPublic = !isset($attributes['public']) || $attributes['public'];
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
        $locateableServices['container'] = new Reference('service_container');

        $container->findDefinition(GraphQLServices::class)->addArgument($locateableServices);
    }
}
