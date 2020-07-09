<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\EventListener;

use Overblog\GraphQLBundle\Generator\TypeGenerator;

final class ClassLoaderListener
{
    private TypeGenerator $typeGenerator;

    public function __construct(TypeGenerator $typeGenerator)
    {
        $this->typeGenerator = $typeGenerator;
    }

    public function load(): void
    {
        $this->typeGenerator->loadClasses();
    }
}
