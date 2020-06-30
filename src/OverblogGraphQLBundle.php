<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle;

use Overblog\GraphQLBundle\DependencyInjection\Compiler\AliasedPass;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\ConfigParserPass;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\ConfigProcessorPass;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\ExpressionFunctionPass;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\GlobalVariablesPass;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\MutationTaggedServiceMappingTaggedPass;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\ResolverMapTaggedServiceMappingPass;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\ResolverMethodAliasesPass;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\ResolverTaggedServiceMappingPass;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\TypeGeneratorPass;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\TypeTaggedServiceMappingPass;
use Overblog\GraphQLBundle\DependencyInjection\OverblogGraphQLExtension;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OverblogGraphQLBundle extends Bundle
{
    public function boot(): void
    {
        if ($this->container->has('overblog_graphql.cache_compiler')) {
            $this->container->get('overblog_graphql.cache_compiler')->loadClasses(); // @phpstan-ignore-line
        }
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        //TypeGeneratorPass must be before TypeTaggedServiceMappingPass
        $container->addCompilerPass(new ConfigParserPass());
        $container->addCompilerPass(new ConfigProcessorPass());
        $container->addCompilerPass(new GlobalVariablesPass());
        $container->addCompilerPass(new ExpressionFunctionPass());
        $container->addCompilerPass(new ResolverMethodAliasesPass());
        $container->addCompilerPass(new AliasedPass());
        $container->addCompilerPass(new ResolverMapTaggedServiceMappingPass());
        $container->addCompilerPass(new TypeGeneratorPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new TypeTaggedServiceMappingPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new ResolverTaggedServiceMappingPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new MutationTaggedServiceMappingTaggedPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }

    public function getContainerExtension()
    {
        if (!$this->extension instanceof ExtensionInterface) {
            $this->extension = new OverblogGraphQLExtension();
        }

        return $this->extension;
    }
}
