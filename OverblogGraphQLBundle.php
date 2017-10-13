<?php

namespace Overblog\GraphQLBundle;

use Overblog\GraphQLBundle\DependencyInjection\Compiler\AutoMappingPass;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\AutowiringTypesPass;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\ConfigTypesPass;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\MutationTaggedServiceMappingTaggedPass;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\ResolverTaggedServiceMappingPass;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\TypeTaggedServiceMappingPass;
use Overblog\GraphQLBundle\DependencyInjection\OverblogGraphQLExtension;
use Overblog\GraphQLBundle\DependencyInjection\OverblogGraphQLTypesExtension;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OverblogGraphQLBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        //ConfigTypesPass and AutoMappingPass must be before TypeTaggedServiceMappingPass
        $container->addCompilerPass(new AutoMappingPass());
        $container->addCompilerPass(new ConfigTypesPass());
        $container->addCompilerPass(new AutowiringTypesPass());

        $container->addCompilerPass(new TypeTaggedServiceMappingPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new ResolverTaggedServiceMappingPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new MutationTaggedServiceMappingTaggedPass(), PassConfig::TYPE_BEFORE_REMOVING);

        $container->registerExtension(new OverblogGraphQLTypesExtension());
    }

    public function getContainerExtension()
    {
        if (!$this->extension instanceof ExtensionInterface) {
            $this->extension = new OverblogGraphQLExtension();
        }

        return $this->extension;
    }
}
