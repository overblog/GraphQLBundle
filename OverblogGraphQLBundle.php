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

use Overblog\GraphQLBundle\DependencyInjection\Compiler\ArgPass;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\FieldPass;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\MutationPass;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\ResolverPass;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\TypePass;
use Overblog\GraphQLBundle\DependencyInjection\OverblogGraphQLExtension;
use Overblog\GraphQLBundle\DependencyInjection\OverblogGraphQLTypesExtension;
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

        $container->addCompilerPass(new TypePass());
        $container->addCompilerPass(new FieldPass());
        $container->addCompilerPass(new ResolverPass());
        $container->addCompilerPass(new MutationPass());
        $container->addCompilerPass(new ArgPass());
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
