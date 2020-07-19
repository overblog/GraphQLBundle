<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator\Annotation;

/**
 * @Annotation
 */
class Model
{
    public string $strategy = 'auto';
    public string $identifier = 'id';
}
