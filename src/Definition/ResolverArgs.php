<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition;

use ArrayObject;
use GraphQL\Type\Definition\ResolveInfo;

class ResolverArgs
{
    public ArgumentInterface $args;
    public ResolveInfo $info;
    public ArrayObject $context;

    /** @var mixed */
    public $value;

    /**
     * @param mixed $value
     */
    public function __construct($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info)
    {
        $this->value = $value;
        $this->args = $args;
        $this->context = $context;
        $this->info = $info;
    }
}
