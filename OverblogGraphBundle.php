<?php

namespace Overblog\GraphBundle;

use Overblog\GraphBundle\DependencyInjection\Compiler\TypePass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OverblogGraphBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TypePass());
    }
}
