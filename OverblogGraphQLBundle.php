<?php

namespace Overblog\GraphQLBundle;

use Overblog\GraphQLBundle\DependencyInjection\Compiler\FieldPass;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\MutationPass;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\ResolverPass;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\TypePass;
use Overblog\GraphQLBundle\DependencyInjection\OverblogGraphQLExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OverblogGraphQLBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TypePass());
        $container->addCompilerPass(new FieldPass());
        $container->addCompilerPass(new ResolverPass());
        $container->addCompilerPass(new MutationPass());
    }

    public function getContainerExtension()
    {
        if (!$this->extension instanceof ExtensionInterface) {
            $this->extension =  new OverblogGraphQLExtension();
        }

        return $this->extension;
    }
}
