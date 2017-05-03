<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle;

use Overblog\GraphQLBundle\DependencyInjection\Compiler\AutoMappingPass;
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

        $container->addCompilerPass(new TypeTaggedServiceMappingPass(), PassConfig::TYPE_OPTIMIZE);
        $container->addCompilerPass(new ResolverTaggedServiceMappingPass(), PassConfig::TYPE_OPTIMIZE);
        $container->addCompilerPass(new MutationTaggedServiceMappingTaggedPass(), PassConfig::TYPE_OPTIMIZE);

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
