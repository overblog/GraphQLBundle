<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Config;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class InterfaceTypeDefinition extends TypeWithOutputFieldsDefinition
{
    public function getDefinition()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('_interface_config');

        $node
            ->children()
                ->append($this->nameSection())
                ->append($this->outputFieldsSelection('fields'))
                ->append($this->resolveTypeSection())
                ->append($this->descriptionSection())
            ->end();

        return $node;
    }
}
