<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\EventListener;

use Overblog\GraphQLBundle\Generator\TypeGenerator;

class ClassLoaderListener
{
    private $typeGenerator;

    public function __construct(TypeGenerator $typeGenerator)
    {
        $this->typeGenerator = $typeGenerator;
    }

    public function load()
    {
        $this->typeGenerator->loadClasses();
    }
}
